<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import axios from 'axios'

// -----------------------------------------------------------------------
// Interfaces
// -----------------------------------------------------------------------

interface Appetite {
    id: number
    code: string
    label: string
    color: string
    score_min: number
    score_max: number
    is_active: boolean
}

interface NomenclatureNode {
    id: number
    code: string
    label: string
    description?: string
    level: number
    type_code: string
    parent_id: number | null
    appetite_id: number | null
    appetite: Appetite | null
    children?: NomenclatureNode[]
}

interface TypeMeta {
    label: string
    color: string
    icon: string
    badge_class: string
}

interface RootNode extends NomenclatureNode {
    type_meta: TypeMeta
}

interface MistralChild {
    label: string
    description: string
}

interface MistralSuggestion {
    label: string
    description: string
    children: MistralChild[]
    // état UI
    _selectedChildren?: Record<number, boolean>
    _selected?: boolean
}

// -----------------------------------------------------------------------
// Props
// -----------------------------------------------------------------------

const props = defineProps<{
    tree: RootNode[]
    appetites: Appetite[]
}>()

// -----------------------------------------------------------------------
// Domaines sectoriels (même liste que impact/fréquence)
// -----------------------------------------------------------------------

const SECTORS = [
    { key: 'finance',    label: 'Finance',    icon: 'ti-coin',          color: '#4361ee' },
    { key: 'agro',       label: 'Agro',       icon: 'ti-plant-2',       color: '#2d6a4f' },
    { key: 'sante',      label: 'Santé',      icon: 'ti-heart-rate-monitor', color: '#e63946' },
    { key: 'industrie',  label: 'Industrie',  icon: 'ti-building-factory-2', color: '#f77f00' },
    { key: 'services',   label: 'Services',   icon: 'ti-briefcase',     color: '#7209b7' },
    { key: 'public',     label: 'Public',     icon: 'ti-building-community', color: '#457b9d' },
    { key: 'tech',       label: 'Tech',       icon: 'ti-circuit-board', color: '#06d6a0' },
]

const TYPES = [
    { code: 'RC', label: 'Risque de Conformite',   color: '#4361ee', icon: 'ti ti-shield-check' },
    { code: 'RF', label: 'Risque Financier',        color: '#e63946', icon: 'ti ti-coin' },
    { code: 'RS', label: 'Risque Strategique',      color: '#7209b7', icon: 'ti ti-chess' },
    { code: 'RO', label: 'Risque Operationnel',     color: '#f77f00', icon: 'ti ti-settings-cog' },
]

// -----------------------------------------------------------------------
// Arborescence — expand / collapse
// -----------------------------------------------------------------------

const expandedL2   = ref<Record<number, boolean>>({})
const toggleL2     = (id: number) => { expandedL2.value[id] = !expandedL2.value[id] }
const isExpandedL2 = (id: number): boolean => !!expandedL2.value[id]

// -----------------------------------------------------------------------
// Modal — Nomenclature (ajout / édition)
// -----------------------------------------------------------------------

const showModal  = ref(false)
const editMode   = ref(false)
const loading    = ref(false)
const errors     = ref<Record<string, string[]>>({})

const form = ref({
    id: null as number | null, parent_id: null as number | null,
    label: '', description: '',
})
const parentCtx = ref<{ id: number; code: string; label: string; level: number } | null>(null)

const nextCodeHint = computed(() => {
    if (!parentCtx.value) return ''
    return parentCtx.value.level === 1 ? `${parentCtx.value.code}-XXX` : `${parentCtx.value.code}-XX`
})

const openAdd = (parent: NomenclatureNode) => {
    editMode.value = false
    form.value = { id: null, parent_id: parent.id, label: '', description: '' }
    parentCtx.value = { id: parent.id, code: parent.code, label: parent.label, level: parent.level }
    errors.value = {}
    showModal.value = true
}

const openEdit = (node: NomenclatureNode) => {
    editMode.value = true
    form.value = { id: node.id, parent_id: node.parent_id, label: node.label, description: node.description ?? '' }
    parentCtx.value = null
    errors.value = {}
    showModal.value = true
}

const closeModal = () => { showModal.value = false }

const submitNomenclature = async () => {
    loading.value = true
    errors.value = {}
    try {
        if (editMode.value && form.value.id) {
            await axios.put(route('risk.core.nomenclature.update', form.value.id), {
                label: form.value.label, description: form.value.description,
            })
        } else {
            await axios.post(route('risk.core.nomenclature.store'), {
                parent_id: form.value.parent_id, label: form.value.label, description: form.value.description,
            })
        }
        showModal.value = false
        router.reload({ only: ['tree'] })
    } catch (e: any) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {}
        else alert(e.response?.data?.message ?? 'Erreur')
    } finally { loading.value = false }
}

const deleteNode = async (node: NomenclatureNode) => {
    if (!confirm(`Supprimer "${node.code} — ${node.label}" ?`)) return
    try {
        await axios.delete(route('risk.core.nomenclature.destroy', node.id))
        router.reload({ only: ['tree'] })
    } catch (e: any) { alert(e.response?.data?.message ?? 'Erreur lors de la suppression') }
}

// -----------------------------------------------------------------------
// Modal — Assignation appétance
// -----------------------------------------------------------------------

const showAppetiteModal  = ref(false)
const appetiteLoading    = ref(false)
const appetiteNodeCtx    = ref<{ id: number; code: string; label: string } | null>(null)
const selectedAppetiteId = ref<number | null>(null)

const openAppetite = (node: NomenclatureNode) => {
    appetiteNodeCtx.value = { id: node.id, code: node.code, label: node.label }
    selectedAppetiteId.value = node.appetite_id
    showAppetiteModal.value = true
}

const closeAppetiteModal = () => { showAppetiteModal.value = false }

const submitAppetite = async () => {
    if (!appetiteNodeCtx.value) return
    appetiteLoading.value = true
    try {
        await axios.put(route('risk.core.nomenclature.assign-appetite', appetiteNodeCtx.value.id),
            { appetite_id: selectedAppetiteId.value })
        showAppetiteModal.value = false
        router.reload({ only: ['tree'] })
    } catch (e: any) { alert(e.response?.data?.message ?? 'Erreur') }
    finally { appetiteLoading.value = false }
}

// -----------------------------------------------------------------------
// Offcanvas — Gestion des niveaux d'appétance (CRUD)
// -----------------------------------------------------------------------

const showOffcanvas     = ref(false)
const offcanvasLoading  = ref(false)
const offcanvasEditMode = ref(false)
const offcanvasErrors   = ref<Record<string, string[]>>({})

const defaultForm = () => ({ id: null as number | null, code: '', label: '', color: '#17a2b8', score_min: 0, score_max: 0 })
const offcanvasForm = ref(defaultForm())

const openOffcanvas = () => {
    offcanvasEditMode.value = false
    offcanvasForm.value = defaultForm()
    offcanvasErrors.value = {}
    showOffcanvas.value = true
    showMistralOffcanvas.value = false
}

const openEditAppetite = (apt: Appetite) => {
    offcanvasEditMode.value = true
    offcanvasForm.value = { id: apt.id, code: apt.code, label: apt.label, color: apt.color, score_min: apt.score_min, score_max: apt.score_max }
    offcanvasErrors.value = {}
    showOffcanvas.value = true
}

const closeOffcanvas = () => { showOffcanvas.value = false }

const submitAppetiteForm = async () => {
    offcanvasLoading.value = true
    offcanvasErrors.value = {}
    try {
        if (offcanvasEditMode.value && offcanvasForm.value.id) {
            await axios.put(route('risk.core.nomenclature.appetites.update', offcanvasForm.value.id), {
                label: offcanvasForm.value.label, color: offcanvasForm.value.color,
                score_min: offcanvasForm.value.score_min, score_max: offcanvasForm.value.score_max,
            })
        } else {
            await axios.post(route('risk.core.nomenclature.appetites.store'), {
                code: offcanvasForm.value.code, label: offcanvasForm.value.label,
                color: offcanvasForm.value.color, score_min: offcanvasForm.value.score_min,
                score_max: offcanvasForm.value.score_max,
            })
        }
        offcanvasForm.value = defaultForm()
        offcanvasErrors.value = {}
        router.reload({ only: ['appetites', 'tree'] })
    } catch (e: any) {
        if (e.response?.status === 422) offcanvasErrors.value = e.response.data.errors ?? {}
        else alert(e.response?.data?.message ?? 'Erreur')
    } finally { offcanvasLoading.value = false }
}

const deleteAppetite = async (apt: Appetite) => {
    if (!confirm(`Supprimer "${apt.code} — ${apt.label}" ?`)) return
    try {
        await axios.delete(route('risk.core.nomenclature.appetites.destroy', apt.id))
        router.reload({ only: ['appetites', 'tree'] })
    } catch (e: any) { alert(e.response?.data?.message ?? 'Impossible de supprimer') }
}

// -----------------------------------------------------------------------
// Offcanvas — Mistral AI
// -----------------------------------------------------------------------

const showMistralOffcanvas = ref(false)

// Étape 1 — sélection type + secteur
const mistralTypeCode  = ref<string>('RC')
const mistralSector    = ref<string>('')
const mistralContext   = ref<string>('')

// État de la requête
type MistralState = 'idle' | 'loading' | 'success' | 'error'
const mistralState    = ref<MistralState>('idle')
const mistralError    = ref<string>('')
const mistralHints    = ref<string[]>([])
const mistralSector_used = ref<string>('')
const mistralTypeUsed    = ref<string>('')
const mistralTypeColor   = ref<string>('#6c757d')

// Suggestions retournées
const suggestions = ref<MistralSuggestion[]>([])

// Importation en cours
const importLoading = ref(false)
const importSuccess = ref(false)

const openMistralOffcanvas = () => {
    showMistralOffcanvas.value = true
    showOffcanvas.value = false
    mistralState.value = 'idle'
    suggestions.value = []
    importSuccess.value = false
    mistralError.value = ''
    mistralHints.value = []
}

const closeMistralOffcanvas = () => { showMistralOffcanvas.value = false }

const selectSector = (key: string) => { mistralSector.value = key }

const callMistral = async () => {
    if (!mistralSector.value) return
    mistralState.value = 'loading'
    mistralError.value = ''
    mistralHints.value = []
    suggestions.value = []
    importSuccess.value = false

    try {
        const { data } = await axios.post(route('risk.core.nomenclature.mistral.suggest'), {
            type_code: mistralTypeCode.value,
            sector:    mistralSector.value,
            context:   mistralContext.value || undefined,
        })

        mistralSector_used.value = data.sector
        mistralTypeUsed.value    = data.type_label
        mistralTypeColor.value   = data.type_color

        // Initialiser l'état de sélection
        suggestions.value = (data.suggestions as MistralSuggestion[]).map(s => ({
            ...s,
            _selected: true,
            _selectedChildren: Object.fromEntries(s.children.map((_, i) => [i, true])),
        }))

        mistralState.value = 'success'
    } catch (e: any) {
        mistralState.value = 'error'
        mistralError.value = e.response?.data?.message ?? 'Erreur inattendue'
        mistralHints.value = e.response?.data?.hints ?? []
    }
}

const toggleSuggestion = (idx: number) => {
    suggestions.value[idx]._selected = !suggestions.value[idx]._selected
}

const toggleChild = (sIdx: number, cIdx: number) => {
    if (!suggestions.value[sIdx]._selectedChildren) return
    suggestions.value[sIdx]._selectedChildren![cIdx] = !suggestions.value[sIdx]._selectedChildren![cIdx]
}

const selectedCount = computed(() =>
    suggestions.value.filter(s => s._selected).length
)

// Trouver le nœud racine correspondant au type sélectionné
const rootNodeForType = computed(() =>
    props.tree.find(r => r.type_code === mistralTypeCode.value) ?? null
)

const importSuggestions = async () => {
    const root = rootNodeForType.value
    if (!root) { alert('Type racine introuvable dans l\'arbre'); return }

    const selected = suggestions.value.filter(s => s._selected)
    if (!selected.length) { alert('Sélectionnez au moins une suggestion'); return }

    importLoading.value = true
    importSuccess.value = false

    try {
        for (const suggestion of selected) {
            // Créer le niveau 2
            const { data: l2Data } = await axios.post(route('risk.core.nomenclature.store'), {
                parent_id:   root.id,
                label:       suggestion.label,
                description: suggestion.description,
            })

            const l2Id = l2Data.nomenclature?.id
            if (!l2Id) continue

            // Créer les enfants niveau 3 sélectionnés
            const children = suggestion.children.filter((_, i) =>
                suggestion._selectedChildren?.[i] !== false
            )

            for (const child of children) {
                await axios.post(route('risk.core.nomenclature.store'), {
                    parent_id:   l2Id,
                    label:       child.label,
                    description: child.description,
                })
            }
        }

        importSuccess.value = true
        suggestions.value = []
        mistralState.value = 'idle'
        router.reload({ only: ['tree'] })

    } catch (e: any) {
        alert(e.response?.data?.message ?? 'Erreur lors de l\'importation')
    } finally {
        importLoading.value = false
    }
}

// -----------------------------------------------------------------------
// Helpers
// -----------------------------------------------------------------------

const totalCount = computed(() =>
    props.tree.reduce((acc, root) => {
        const l2 = root.children?.length ?? 0
        const l3 = root.children?.reduce((s, c) => s + (c.children?.length ?? 0), 0) ?? 0
        return acc + l2 + l3
    }, 0)
)

const activeAppetites = computed(() => props.appetites.filter(a => a.is_active))

const selectedType = computed(() => TYPES.find(t => t.code === mistralTypeCode.value))
</script>

<template>
    <VerticalLayout>
        <div class="container-fluid py-4">

            <!-- ── Header ───────────────────────────────────────────── -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-1">
                        <i class="ti ti-sitemap me-2 text-primary"></i>Nomenclature des Risques
                    </h4>
                    <p class="text-muted mb-0">
                        4 catégories de base — 3 niveaux hiérarchiques —
                        <strong>{{ totalCount }}</strong> nomenclature(s) créée(s)
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <!-- Bouton Mistral AI -->
                    <button class="btn btn-outline-primary d-flex align-items-center gap-2"
                            @click="openMistralOffcanvas">
                        <i class="ti ti-sparkles"></i>
                        Suggestions IA
                    </button>
                    <!-- Bouton appétances -->
                    <button class="btn btn-outline-warning d-flex align-items-center gap-2"
                            @click="openOffcanvas">
                        <i class="ti ti-flame"></i>
                        Gérer les appétances
                        <span class="badge bg-warning text-dark rounded-pill">{{ appetites.length }}</span>
                    </button>
                </div>
            </div>

            <!-- ── 4 blocs de type ──────────────────────────────────── -->
            <div class="row g-3">
                <div v-for="root in tree" :key="root.id" class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header d-flex align-items-center py-3 rounded-top"
                             :style="{ borderLeft: `4px solid ${root.type_meta.color}`, background: `${root.type_meta.color}12` }">
                            <i :class="`${root.type_meta.icon} fs-5 me-2`"
                               :style="{ color: root.type_meta.color }"></i>
                            <div class="flex-grow-1">
                                <span class="fw-bold me-1" :style="{ color: root.type_meta.color }">{{ root.code }}</span>
                                <span class="fw-semibold text-dark">{{ root.type_meta.label }}</span>
                                <span class="badge rounded-pill ms-2 small"
                                      :style="{ background: root.type_meta.color, color: '#fff' }">
                                    Niveau 1 — Racine
                                </span>
                            </div>
                            <span class="text-muted small me-3">{{ root.children?.length ?? 0 }} sous-catégorie(s)</span>
                            <button class="btn btn-sm btn-primary" @click="openAdd(root)">
                                <i class="ti ti-plus me-1"></i>Ajouter
                            </button>
                        </div>

                        <div class="card-body p-0">
                            <div v-if="!root.children?.length" class="text-center py-5 text-muted">
                                <i class="ti ti-folder-off d-block fs-2 mb-2"></i>
                                <p class="mb-0 small">Aucune nomenclature — cliquez <strong>Ajouter</strong> pour commencer</p>
                            </div>

                            <ul class="list-group list-group-flush">
                                <li v-for="child in root.children" :key="child.id" class="list-group-item p-0">
                                    <div class="d-flex align-items-center px-3 py-2"
                                         :class="{ 'border-bottom': !isExpandedL2(child.id) }">
                                        <button class="btn btn-link btn-sm p-0 me-2 text-muted" @click="toggleL2(child.id)">
                                            <i :class="`ti ${isExpandedL2(child.id) ? 'ti-chevron-down' : 'ti-chevron-right'}`"></i>
                                        </button>
                                        <code class="me-2 text-primary small fw-bold">{{ child.code }}</code>
                                        <span class="flex-grow-1">{{ child.label }}</span>
                                        <span v-if="child.appetite" class="badge rounded-pill me-2 small"
                                              :style="{ background: child.appetite.color+'22', color: child.appetite.color, border: `1px solid ${child.appetite.color}55` }">
                                            <i class="ti ti-flame me-1" style="font-size:.65rem"></i>{{ child.appetite.label }}
                                        </span>
                                        <span v-else class="badge bg-light text-muted border rounded-pill me-2 small">
                                            <i class="ti ti-flame-off me-1" style="font-size:.65rem"></i>Aucune
                                        </span>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill me-3 small">
                                            Niv. 2 — {{ child.children?.length ?? 0 }} enfant(s)
                                        </span>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-warning" title="Appétance" @click="openAppetite(child)"><i class="ti ti-flame"></i></button>
                                            <button class="btn btn-outline-secondary" title="Ajouter sous-catégorie" @click="openAdd(child)"><i class="ti ti-plus"></i></button>
                                            <button class="btn btn-outline-primary" title="Modifier" @click="openEdit(child)"><i class="ti ti-pencil"></i></button>
                                            <button class="btn btn-outline-danger" title="Supprimer" @click="deleteNode(child)"><i class="ti ti-trash"></i></button>
                                        </div>
                                    </div>

                                    <Transition name="slide-down">
                                        <div v-if="isExpandedL2(child.id)" class="border-bottom" style="background:#f8f9fb;">
                                            <div v-if="!child.children?.length" class="text-muted small px-5 py-2">
                                                <i class="ti ti-dots me-1"></i>Aucun sous-élément
                                            </div>
                                            <div v-for="gc in child.children" :key="gc.id"
                                                 class="d-flex align-items-center px-5 py-2 border-bottom gc-row">
                                                <i class="ti ti-corner-down-right me-2 text-muted small"></i>
                                                <code class="me-2 text-success small fw-bold">{{ gc.code }}</code>
                                                <span class="flex-grow-1 small">{{ gc.label }}</span>
                                                <span v-if="gc.appetite" class="badge rounded-pill me-2 small"
                                                      :style="{ background: gc.appetite.color+'22', color: gc.appetite.color, border: `1px solid ${gc.appetite.color}55` }">
                                                    <i class="ti ti-flame me-1" style="font-size:.65rem"></i>{{ gc.appetite.label }}
                                                </span>
                                                <span v-else class="badge bg-light text-muted border rounded-pill me-2 small">
                                                    <i class="ti ti-flame-off me-1" style="font-size:.65rem"></i>Aucune
                                                </span>
                                                <span class="badge bg-light text-muted border rounded-pill me-3 small">Niv. 3</span>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-warning" title="Appétance" @click="openAppetite(gc)"><i class="ti ti-flame"></i></button>
                                                    <button class="btn btn-outline-primary" title="Modifier" @click="openEdit(gc)"><i class="ti ti-pencil"></i></button>
                                                    <button class="btn btn-outline-danger" title="Supprimer" @click="deleteNode(gc)"><i class="ti ti-trash"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </Transition>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================
             Modal — Nomenclature
        ============================================================ -->
        <Teleport to="body">
            <div v-if="showModal" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i :class="`ti ${editMode ? 'ti-pencil' : 'ti-plus'} me-2`"></i>
                                {{ editMode ? 'Modifier la nomenclature' : 'Nouvelle nomenclature' }}
                            </h5>
                            <button class="btn-close" @click="closeModal"></button>
                        </div>
                        <div class="modal-body">
                            <div v-if="!editMode && parentCtx" class="alert alert-info py-2 mb-3 small">
                                <i class="ti ti-sitemap me-1"></i>
                                Sous <strong>{{ parentCtx.code }}</strong> — {{ parentCtx.label }}
                                <span class="badge bg-info-subtle text-info ms-2">Niveau {{ parentCtx.level + 1 }}</span>
                                <span class="ms-2 text-muted">Code auto : <code>{{ nextCodeHint }}</code></span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Libellé <span class="text-danger">*</span></label>
                                <input v-model="form.label" type="text" class="form-control"
                                       :class="{ 'is-invalid': errors.label }" placeholder="Ex : Risque fiscal" />
                                <div v-if="errors.label" class="invalid-feedback">{{ errors.label[0] }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea v-model="form.description" class="form-control" rows="3"
                                          placeholder="Description optionnelle..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" @click="closeModal">Annuler</button>
                            <button class="btn btn-primary" :disabled="loading" @click="submitNomenclature">
                                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                                {{ editMode ? 'Enregistrer' : 'Créer' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ============================================================
             Modal — Assignation appétance
        ============================================================ -->
        <Teleport to="body">
            <div v-if="showAppetiteModal" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="ti ti-flame me-2 text-warning"></i>Appétance au risque</h5>
                            <button class="btn-close" @click="closeAppetiteModal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-light border py-2 mb-3 small">
                                <i class="ti ti-tag me-1 text-muted"></i>
                                <strong>{{ appetiteNodeCtx?.code }}</strong> — {{ appetiteNodeCtx?.label }}
                            </div>
                            <label class="form-label fw-semibold">Niveau d'appétance</label>
                            <div class="d-flex flex-column gap-2">
                                <label class="appetite-option d-flex align-items-center gap-2 px-3 py-2 rounded border"
                                       :class="{ 'appetite-option--selected': selectedAppetiteId === null }" style="cursor:pointer">
                                    <input type="radio" class="form-check-input mt-0"
                                           :checked="selectedAppetiteId === null" @change="selectedAppetiteId = null" />
                                    <i class="ti ti-flame-off text-muted"></i>
                                    <span class="small text-muted">Aucune appétance</span>
                                </label>
                                <label v-for="apt in activeAppetites" :key="apt.id"
                                       class="appetite-option d-flex align-items-center gap-2 px-3 py-2 rounded border"
                                       :class="{ 'appetite-option--selected': selectedAppetiteId === apt.id }"
                                       :style="{ cursor:'pointer', borderColor: selectedAppetiteId===apt.id ? apt.color:'', background: selectedAppetiteId===apt.id ? apt.color+'15':'' }">
                                    <input type="radio" class="form-check-input mt-0"
                                           :checked="selectedAppetiteId === apt.id" @change="selectedAppetiteId = apt.id" />
                                    <span class="rounded-circle d-inline-block flex-shrink-0"
                                          :style="{ width:'10px', height:'10px', background: apt.color }"></span>
                                    <span class="small fw-semibold" :style="{ color: apt.color }">{{ apt.label }}</span>
                                    <span class="text-muted small ms-auto">{{ apt.score_min }} – {{ apt.score_max }}</span>
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary btn-sm" @click="closeAppetiteModal">Annuler</button>
                            <button class="btn btn-warning btn-sm" :disabled="appetiteLoading" @click="submitAppetite">
                                <span v-if="appetiteLoading" class="spinner-border spinner-border-sm me-1"></span>
                                <i v-else class="ti ti-check me-1"></i>Appliquer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ============================================================
             Offcanvas — Gestion des appétances
        ============================================================ -->
        <Teleport to="body">
            <div v-if="showOffcanvas" class="offcanvas-backdrop fade show" @click="closeOffcanvas"></div>
            <div class="offcanvas offcanvas-end" :class="{ show: showOffcanvas }" style="width:420px;visibility:visible">
                <div class="offcanvas-header border-bottom">
                    <h5 class="offcanvas-title"><i class="ti ti-flame me-2 text-warning"></i>Niveaux d'appétance</h5>
                    <button class="btn-close" @click="closeOffcanvas"></button>
                </div>
                <div class="offcanvas-body d-flex flex-column gap-4 py-3">
                    <div>
                        <h6 class="text-muted fw-semibold small text-uppercase mb-2">Niveaux configurés ({{ appetites.length }})</h6>
                        <div v-if="!appetites.length" class="text-center py-4 text-muted small">
                            <i class="ti ti-flame-off d-block fs-3 mb-2"></i>Aucun niveau configuré
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <div v-for="apt in appetites" :key="apt.id"
                                 class="d-flex align-items-center gap-2 px-3 py-2 rounded border bg-white apt-row">
                                <span class="rounded-circle flex-shrink-0" :style="{ width:'12px', height:'12px', background: apt.color }"></span>
                                <div class="flex-grow-1">
                                    <span class="fw-semibold small" :style="{ color: apt.color }">{{ apt.label }}</span>
                                    <span class="text-muted small ms-2">{{ apt.code }}</span>
                                </div>
                                <span class="badge bg-light text-muted border rounded-pill small me-1">{{ apt.score_min }}–{{ apt.score_max }}</span>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary btn-sm" @click="openEditAppetite(apt)"><i class="ti ti-pencil"></i></button>
                                    <button class="btn btn-outline-danger btn-sm" @click="deleteAppetite(apt)"><i class="ti ti-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="border rounded-2 p-3" style="background:#f8f9fb;">
                        <h6 class="fw-semibold mb-3">
                            <i :class="`ti ${offcanvasEditMode ? 'ti-pencil' : 'ti-plus'} me-1 text-warning`"></i>
                            {{ offcanvasEditMode ? 'Modifier le niveau' : 'Nouveau niveau' }}
                        </h6>
                        <div v-if="!offcanvasEditMode" class="mb-3">
                            <label class="form-label fw-semibold small">Code <span class="text-danger">*</span></label>
                            <input v-model="offcanvasForm.code" type="text" class="form-control form-control-sm"
                                   :class="{ 'is-invalid': offcanvasErrors.code }" placeholder="Ex : APT-5"
                                   style="text-transform:uppercase" />
                            <div v-if="offcanvasErrors.code" class="invalid-feedback">{{ offcanvasErrors.code[0] }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Label <span class="text-danger">*</span></label>
                            <input v-model="offcanvasForm.label" type="text" class="form-control form-control-sm"
                                   :class="{ 'is-invalid': offcanvasErrors.label }" placeholder="Ex : Très élevé" />
                            <div v-if="offcanvasErrors.label" class="invalid-feedback">{{ offcanvasErrors.label[0] }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Couleur <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-center gap-2">
                                <input v-model="offcanvasForm.color" type="color" class="form-control form-control-color flex-shrink-0" style="width:44px;height:34px;padding:2px 4px" />
                                <input v-model="offcanvasForm.color" type="text" class="form-control form-control-sm" :class="{ 'is-invalid': offcanvasErrors.color }" placeholder="#17a2b8" maxlength="7" />
                                <span class="rounded-pill px-3 py-1 small fw-semibold flex-shrink-0"
                                      :style="{ background: offcanvasForm.color+'22', color: offcanvasForm.color, border: `1px solid ${offcanvasForm.color}55` }">
                                    {{ offcanvasForm.label || 'Aperçu' }}
                                </span>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Score min <span class="text-danger">*</span></label>
                                <input v-model.number="offcanvasForm.score_min" type="number" class="form-control form-control-sm" min="0" />
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Score max <span class="text-danger">*</span></label>
                                <input v-model.number="offcanvasForm.score_max" type="number" class="form-control form-control-sm" :min="offcanvasForm.score_min" />
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button v-if="offcanvasEditMode" class="btn btn-sm btn-outline-secondary"
                                    @click="offcanvasForm = defaultForm(); offcanvasEditMode = false">
                                <i class="ti ti-x me-1"></i>Annuler
                            </button>
                            <button class="btn btn-sm btn-warning flex-grow-1" :disabled="offcanvasLoading" @click="submitAppetiteForm">
                                <span v-if="offcanvasLoading" class="spinner-border spinner-border-sm me-1"></span>
                                <i v-else :class="`ti ${offcanvasEditMode ? 'ti-check' : 'ti-plus'} me-1`"></i>
                                {{ offcanvasEditMode ? 'Enregistrer' : 'Créer le niveau' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ============================================================
             Offcanvas — Mistral AI Suggestions
        ============================================================ -->
        <Teleport to="body">
            <div v-if="showMistralOffcanvas" class="offcanvas-backdrop fade show" @click="closeMistralOffcanvas"></div>
            <div class="offcanvas offcanvas-end" :class="{ show: showMistralOffcanvas }"
                 style="width:520px;visibility:visible">

                <div class="offcanvas-header border-bottom"
                     style="background: linear-gradient(135deg, #667eea15, #764ba215);">
                    <h5 class="offcanvas-title">
                        <i class="ti ti-sparkles me-2 text-primary"></i>
                        Suggestions IA — Nomenclature
                    </h5>
                    <button class="btn-close" @click="closeMistralOffcanvas"></button>
                </div>

                <div class="offcanvas-body py-3 d-flex flex-column gap-4">

                    <!-- ── Étape 1 : Sélection type de risque ─────── -->
                    <div>
                        <label class="form-label fw-semibold small text-uppercase text-muted">
                            <i class="ti ti-tag me-1"></i>Type de risque cible
                        </label>
                        <div class="d-flex flex-wrap gap-2">
                            <button v-for="t in TYPES" :key="t.code"
                                    class="btn btn-sm"
                                    :class="mistralTypeCode === t.code ? 'active' : 'btn-outline-secondary'"
                                    :style="mistralTypeCode === t.code
                                        ? { background: t.color, borderColor: t.color, color: '#fff' }
                                        : {}"
                                    @click="mistralTypeCode = t.code">
                                <i :class="`${t.icon} me-1`"></i>{{ t.code }}
                            </button>
                        </div>
                        <div v-if="selectedType" class="mt-2 small text-muted">
                            <i :class="`${selectedType.icon} me-1`" :style="{ color: selectedType.color }"></i>
                            {{ selectedType.label }}
                        </div>
                    </div>

                    <!-- ── Étape 2 : Secteur ──────────────────────── -->
                    <div>
                        <label class="form-label fw-semibold small text-uppercase text-muted">
                            <i class="ti ti-building me-1"></i>Secteur d'activité
                        </label>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <button v-for="s in SECTORS" :key="s.key"
                                    class="btn btn-sm"
                                    :class="mistralSector === s.key ? 'active' : 'btn-outline-secondary'"
                                    :style="mistralSector === s.key
                                        ? { background: s.color, borderColor: s.color, color: '#fff' }
                                        : {}"
                                    @click="selectSector(s.key)">
                                <i :class="`ti ${s.icon} me-1`"></i>{{ s.label }}
                            </button>
                        </div>
                        <input v-model="mistralSector" type="text" class="form-control form-control-sm"
                               placeholder="Ou saisir un secteur personnalisé..." />
                    </div>

                    <!-- ── Étape 3 : Contexte optionnel ─────────── -->
                    <div>
                        <label class="form-label fw-semibold small text-uppercase text-muted">
                            <i class="ti ti-notes me-1"></i>Contexte supplémentaire
                            <span class="text-muted fw-normal">(optionnel)</span>
                        </label>
                        <textarea v-model="mistralContext" class="form-control form-control-sm" rows="2"
                                  placeholder="Ex : PME de 50 personnes, certifiée ISO 9001, export vers l'UE..."></textarea>
                    </div>

                    <!-- ── Bouton génération ──────────────────────── -->
                    <button class="btn btn-primary d-flex align-items-center justify-content-center gap-2"
                            :disabled="!mistralSector || mistralState === 'loading'"
                            @click="callMistral">
                        <span v-if="mistralState === 'loading'"
                              class="spinner-border spinner-border-sm"></span>
                        <i v-else class="ti ti-sparkles"></i>
                        {{ mistralState === 'loading' ? 'Génération en cours...' : 'Générer des suggestions' }}
                    </button>

                    <!-- ── État erreur ────────────────────────────── -->
                    <div v-if="mistralState === 'error'">
                        <div class="alert alert-danger py-2 small mb-2">
                            <i class="ti ti-alert-circle me-1"></i>{{ mistralError }}
                        </div>
                        <div v-if="mistralHints.length" class="alert alert-warning py-2 small">
                            <p class="fw-semibold mb-1"><i class="ti ti-bulb me-1"></i>Suggestions :</p>
                            <ul class="mb-0 ps-3">
                                <li v-for="h in mistralHints" :key="h">{{ h }}</li>
                            </ul>
                        </div>
                    </div>

                    <!-- ── Résultats ──────────────────────────────── -->
                    <div v-if="mistralState === 'success' && suggestions.length">

                        <!-- Bandeau contexte -->
                        <div class="alert py-2 small mb-3"
                             :style="{ background: mistralTypeColor+'15', borderColor: mistralTypeColor+'40', color: '#333' }">
                            <i class="ti ti-sparkles me-1" :style="{ color: mistralTypeColor }"></i>
                            <strong>{{ mistralTypeUsed }}</strong> — secteur
                            <strong>{{ mistralSector_used }}</strong> —
                            <span class="text-muted">{{ suggestions.length }} catégorie(s) suggérée(s)</span>
                        </div>

                        <!-- Liste des suggestions -->
                        <div class="d-flex flex-column gap-2">
                            <div v-for="(s, sIdx) in suggestions" :key="sIdx"
                                 class="border rounded-2 overflow-hidden"
                                 :class="{ 'opacity-50': !s._selected }"
                                 :style="s._selected ? { borderColor: mistralTypeColor+'50' } : {}">

                                <!-- En-tête niveau 2 -->
                                <div class="d-flex align-items-start gap-2 px-3 py-2"
                                     :style="{ background: s._selected ? mistralTypeColor+'10' : '#f8f9fa' }">
                                    <div class="form-check mb-0 mt-1">
                                        <input type="checkbox" class="form-check-input"
                                               :checked="s._selected"
                                               @change="toggleSuggestion(sIdx)" />
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="fw-semibold small" :style="{ color: mistralTypeColor }">
                                            {{ s.label }}
                                        </span>
                                        <p v-if="s.description" class="text-muted mb-0" style="font-size:.75rem">
                                            {{ s.description }}
                                        </p>
                                    </div>
                                    <span class="badge rounded-pill small"
                                          :style="{ background: mistralTypeColor+'20', color: mistralTypeColor }">
                                        Niv. 2
                                    </span>
                                </div>

                                <!-- Enfants niveau 3 -->
                                <div v-for="(c, cIdx) in s.children" :key="cIdx"
                                     class="d-flex align-items-start gap-2 px-4 py-2 border-top"
                                     style="background:#fafafa;">
                                    <div class="form-check mb-0 mt-1">
                                        <input type="checkbox" class="form-check-input"
                                               :checked="s._selectedChildren?.[cIdx] !== false"
                                               :disabled="!s._selected"
                                               @change="toggleChild(sIdx, cIdx)" />
                                    </div>
                                    <i class="ti ti-corner-down-right text-muted mt-1" style="font-size:.8rem"></i>
                                    <div class="flex-grow-1">
                                        <span class="small text-success fw-semibold">{{ c.label }}</span>
                                        <p v-if="c.description" class="text-muted mb-0" style="font-size:.72rem">
                                            {{ c.description }}
                                        </p>
                                    </div>
                                    <span class="badge rounded-pill small bg-light text-muted border">Niv. 3</span>
                                </div>
                            </div>
                        </div>

                        <!-- Bouton import -->
                        <div class="mt-3">
                            <div v-if="importSuccess" class="alert alert-success py-2 small mb-2">
                                <i class="ti ti-check me-1"></i>
                                Nomenclatures importées avec succès !
                            </div>
                            <button class="btn btn-success w-100 d-flex align-items-center justify-content-center gap-2"
                                    :disabled="importLoading || selectedCount === 0"
                                    @click="importSuggestions">
                                <span v-if="importLoading" class="spinner-border spinner-border-sm"></span>
                                <i v-else class="ti ti-download"></i>
                                Importer {{ selectedCount }} suggestion(s) sélectionnée(s)
                            </button>
                        </div>
                    </div>

                    <!-- ── État vide après succès ─────────────────── -->
                    <div v-if="mistralState === 'success' && !suggestions.length"
                         class="alert alert-secondary small py-3 text-center">
                        <i class="ti ti-mood-empty d-block fs-3 mb-2"></i>
                        Aucune suggestion retournée — reformulez le secteur ou ajoutez du contexte.
                    </div>

                </div>
            </div>
        </Teleport>

    </VerticalLayout>
</template>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active { transition: all .2s ease; overflow: hidden; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; max-height: 0; }
.slide-down-enter-to,   .slide-down-leave-from { opacity: 1; max-height: 600px; }
.gc-row { transition: background .1s; }
.gc-row:hover { background: #eef0f5 !important; }
.appetite-option { transition: all .15s ease; background: #fff; }
.appetite-option:hover { background: #f8f9fa !important; }
.appetite-option--selected { font-weight: 500; }
.apt-row { transition: background .1s; }
.apt-row:hover { background: #f8f9fa !important; }
</style>
