<script setup lang="ts">
import axios from 'axios'
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

// ─── Interfaces ───────────────────────────────────────────────────────────────

interface Appetite {
    id: number
    code: string
    label: string
    color: string
    description?: string
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

interface TypeMeta { label: string; color: string; icon: string; badge_class: string }

interface RootNode extends NomenclatureNode {
    type_meta: TypeMeta
    children: NomenclatureNode[]
}

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps<{ tree: RootNode[]; appetites: Appetite[] }>()

// ─── State arborescence ───────────────────────────────────────────────────────

const expandedRoots = ref<Record<number, boolean>>({})
const toggleRoot    = (id: number) => { expandedRoots.value[id] = !expandedRoots.value[id] }

// ─── Modal : ajout / édition facteur ─────────────────────────────────────────

const showModal = ref(false)
const editMode  = ref(false)
const loading   = ref(false)
const errors    = ref<Record<string, string[]>>({})
const form      = ref({ id: null as number|null, parent_id: null as number|null, label: '', description: '' })
const parentCtx = ref<{ code: string; label: string } | null>(null)

const openAdd = (root: RootNode) => {
    editMode.value = false
    form.value = { id: null, parent_id: root.id, label: '', description: '' }
    parentCtx.value = { code: root.code, label: root.type_meta.label }
    errors.value = {}; showModal.value = true
}
const openEdit = (node: NomenclatureNode) => {
    editMode.value = true
    form.value = { id: node.id, parent_id: node.parent_id, label: node.label, description: node.description ?? '' }
    parentCtx.value = null; errors.value = {}; showModal.value = true
}
const submitNomenclature = async () => {
    loading.value = true; errors.value = {}
    try {
        if (editMode.value && form.value.id) {
            await axios.put(route('risk.core.nomenclature.update', form.value.id),
                { label: form.value.label, description: form.value.description })
        } else {
            await axios.post(route('risk.core.nomenclature.store'),
                { parent_id: form.value.parent_id, label: form.value.label, description: form.value.description })
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
    } catch (e: any) { alert(e.response?.data?.message ?? 'Erreur') }
}

// ─── Offcanvas : assignation appétence ───────────────────────────────────────
// Workflow : ouvrir panel → choisir appétence → IA génère la description AUTO

const showAppetitePanel   = ref(false)
const appetiteNode        = ref<NomenclatureNode | null>(null)
const selectedAppetiteId  = ref<number | null>(null)
const appetiteDescription = ref('')
const appetiteLoading     = ref(false)
const aiLoading           = ref(false)

const openAppetitePanel = (node: NomenclatureNode) => {
    appetiteNode.value        = node
    selectedAppetiteId.value  = node.appetite_id
    appetiteDescription.value = node.description ?? ''
    showAppetitePanel.value   = true
}

const closeAppetitePanel = () => {
    showAppetitePanel.value = false
}

const selectedAppetite = computed(() =>
    props.appetites.find(a => a.id === selectedAppetiteId.value) ?? null
)

// Génère la description IA via le endpoint Mistral
const generateDescription = async () => {
    if (!appetiteNode.value || !selectedAppetite.value) return
    aiLoading.value = true
    appetiteDescription.value = ''
    try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        const res  = await fetch(route('risk.core.nomenclature.mistral.suggest-appetite'), {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                risk_code:      appetiteNode.value.code,
                risk_label:     appetiteNode.value.label,
                appetite_label: selectedAppetite.value.label,
            }),
        })
        const data = await res.json()
        if (!res.ok) throw new Error(data.message ?? 'Erreur IA')
        appetiteDescription.value = data.description ?? ''
    } catch (e: any) {
        appetiteDescription.value = ''
        alert(e.message ?? 'Erreur lors de la génération IA')
    } finally { aiLoading.value = false }
}

// Quand l'utilisateur sélectionne un niveau d'appétence :
// → on met à jour l'id, on vide la description, et on lance l'IA automatiquement
const onAppetiteChange = (id: number | null) => {
    selectedAppetiteId.value  = id
    appetiteDescription.value = ''
    if (id !== null) {
        // Déclenchement auto de l'IA dès la sélection
        generateDescription()
    }
}

const saveAppetite = async () => {
    if (!appetiteNode.value) return
    appetiteLoading.value = true
    try {
        await axios.put(
            route('risk.core.nomenclature.assign-appetite', appetiteNode.value.id),
            { appetite_id: selectedAppetiteId.value, description: appetiteDescription.value }
        )
        showAppetitePanel.value = false
        router.reload({ only: ['tree'] })
    } catch (e: any) {
        alert(e.response?.data?.message ?? 'Erreur')
    } finally { appetiteLoading.value = false }
}

// ─── Offcanvas : CRUD niveaux d'appétence ─────────────────────────────────────

const showAptPanel = ref(false)
const aptEditMode  = ref(false)
const aptLoading   = ref(false)
const aptErrors    = ref<Record<string, string[]>>({})

const PRESETS = [
    { code: 'AVERSE',  label: 'Averse',  color: '#dc3545', score_min: 0,  score_max: 3  },
    { code: 'PRUDENT', label: 'Prudent', color: '#fd7e14', score_min: 4,  score_max: 8  },
    { code: 'MODERE',  label: 'Modéré',  color: '#ffc107', score_min: 9,  score_max: 15 },
    { code: 'OUVERT',  label: 'Ouvert',  color: '#198754', score_min: 16, score_max: 25 },
]

const defApt = () => ({ id: null as number|null, code: '', label: '', color: '#dc3545', description: '', score_min: 0, score_max: 0 })
const aptForm = ref(defApt())

const openAptCreate = () => { aptEditMode.value = false; aptForm.value = defApt(); aptErrors.value = {} }
const openAptEdit   = (apt: Appetite) => {
    aptEditMode.value = true
    aptForm.value = { id: apt.id, code: apt.code, label: apt.label, color: apt.color, description: apt.description ?? '', score_min: apt.score_min, score_max: apt.score_max }
    aptErrors.value = {}
}
const applyPreset = (p: typeof PRESETS[0]) => {
    aptForm.value.code      = p.code
    aptForm.value.label     = p.label
    aptForm.value.color     = p.color
    aptForm.value.score_min = p.score_min
    aptForm.value.score_max = p.score_max
}
const submitApt = async () => {
    aptLoading.value = true; aptErrors.value = {}
    try {
        if (aptEditMode.value && aptForm.value.id) {
            await axios.put(route('risk.core.nomenclature.appetites.update', aptForm.value.id), {
                label: aptForm.value.label, color: aptForm.value.color,
                description: aptForm.value.description,
                score_min: aptForm.value.score_min, score_max: aptForm.value.score_max,
            })
        } else {
            await axios.post(route('risk.core.nomenclature.appetites.store'), {
                code: aptForm.value.code, label: aptForm.value.label,
                color: aptForm.value.color, description: aptForm.value.description,
                score_min: aptForm.value.score_min, score_max: aptForm.value.score_max,
            })
        }
        aptForm.value = defApt(); aptEditMode.value = false
        router.reload({ only: ['appetites', 'tree'] })
    } catch (e: any) {
        if (e.response?.status === 422) aptErrors.value = e.response.data.errors ?? {}
        else alert(e.response?.data?.message ?? 'Erreur')
    } finally { aptLoading.value = false }
}
const deleteApt = async (apt: Appetite) => {
    if (!confirm(`Supprimer le niveau "${apt.label}" ?`)) return
    try {
        await axios.delete(route('risk.core.nomenclature.appetites.destroy', apt.id))
        router.reload({ only: ['appetites', 'tree'] })
    } catch (e: any) { alert(e.response?.data?.message ?? 'Impossible de supprimer') }
}

// ─── Computed ─────────────────────────────────────────────────────────────────

const activeAppetites = computed(() => props.appetites.filter(a => a.is_active))
const totalFactors    = computed(() => props.tree.reduce((s, r) => s + (r.children?.length ?? 0), 0))
const withAppetite    = computed(() => props.tree.reduce((s, r) => s + (r.children?.filter(c => c.appetite_id).length ?? 0), 0))
const typeColor       = (code: string) =>
    ({ RC: '#4361ee', RF: '#e63946', RS: '#7209b7', RO: '#f77f00' }[code] ?? '#6c757d')
</script>

<template>
    <VerticalLayout>
        <div class="container-fluid py-3">

            <!-- HEADER -->
            <div class="d-flex align-items-start justify-content-between mb-3 flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold mb-1">
                        <i class="ti ti-sitemap me-2 text-primary"></i>Types/Categories/Nomenclatures des Risques
                    </h4>
                    <p class="text-muted mb-0 small">
                        <strong>{{ totalFactors }}</strong> facteur(s) —
                        <strong>{{ withAppetite }}</strong> avec appétence assignée
                    </p>
                </div>
                <button class="btn btn-sm btn-warning" @click="showAptPanel = true; openAptCreate()">
                    <i class="ti ti-flame me-1"></i>Gérer les appétences
                    <span class="badge bg-dark ms-1 rounded-pill">{{ appetites.length }}</span>
                </button>
            </div>

            <!-- Chips appétences actives -->
            <div v-if="activeAppetites.length" class="d-flex flex-wrap gap-2 mb-3">
                <span v-for="apt in activeAppetites" :key="apt.id"
                      class="badge rounded-pill px-3 py-2"
                      :style="{ background: apt.color+'22', color: apt.color, border:`1px solid ${apt.color}55`, fontSize:'.78rem' }">
                    <i class="ti ti-flame me-1"></i>{{ apt.label }}
                    <span class="opacity-60 ms-1">{{ apt.score_min }}–{{ apt.score_max }}</span>
                </span>
            </div>
            <div v-else class="alert alert-warning py-2 px-3 mb-3 small d-flex align-items-center gap-2">
                <i class="ti ti-alert-triangle"></i>
                Aucun niveau d'appétence configuré.
                <button class="btn btn-sm btn-warning py-0 ms-2"
                        @click="showAptPanel = true; openAptCreate()">
                    Créer maintenant
                </button>
            </div>

            <!-- GRILLE des types de risque -->
            <div class="d-flex flex-column gap-3">
                <div v-for="root in tree" :key="root.id" class="card border-0 shadow-sm">

                    <!-- En-tête type -->
                    <div class="card-header d-flex align-items-center py-2 px-3"
                         :style="{ borderLeft:`4px solid ${root.type_meta.color}`, background:`${root.type_meta.color}10` }">
                        <button class="btn btn-link btn-sm p-0 me-2 text-muted" @click="toggleRoot(root.id)">
                            <i :class="`ti ${expandedRoots[root.id] ? 'ti-chevron-down' : 'ti-chevron-right'}`"></i>
                        </button>
                        <i :class="`${root.type_meta.icon} fs-5 me-2`" :style="{ color: root.type_meta.color }"></i>
                        <div class="flex-grow-1">
                            <span class="fw-bold me-1" :style="{ color: root.type_meta.color }">{{ root.code }}</span>
                            <span class="fw-semibold">{{ root.type_meta.label }}</span>
                            <span class="badge ms-2 rounded-pill small"
                                  :style="{ background: root.type_meta.color, color:'#fff' }">
                                {{ root.children?.length ?? 0 }} facteur(s)
                            </span>
                        </div>
                        <!-- Barre progression appétences -->
                        <div class="d-none d-md-flex align-items-center gap-2 me-3" style="min-width:130px">
                            <small class="text-muted" style="font-size:.68rem; white-space:nowrap">
                                {{ root.children?.filter(c => c.appetite_id).length ?? 0 }}/{{ root.children?.length ?? 0 }}
                            </small>
                            <div class="progress flex-grow-1" style="height:5px">
                                <div class="progress-bar"
                                     :style="{
                                         width: root.children?.length
                                             ? ((root.children.filter(c=>c.appetite_id).length / root.children.length)*100)+'%'
                                             : '0%',
                                         background: root.type_meta.color
                                     }"></div>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-primary" @click="openAdd(root)">
                            <i class="ti ti-plus me-1"></i>Facteur
                        </button>
                    </div>

                    <!-- Tableau des facteurs -->
                    <Transition name="slide">
                        <div v-if="expandedRoots[root.id] !== false">
                            <div v-if="!root.children?.length" class="text-center py-4 text-muted small">
                                <i class="ti ti-folder-off d-block fs-2 mb-1 opacity-25"></i>
                                Aucun facteur — cliquez <strong>Facteur</strong> pour commencer
                            </div>
                            <table v-else class="table table-sm table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:110px">Code</th>
                                        <th>Facteur / Critère</th>
                                        <th style="width:160px">Appétence</th>
                                        <th class="text-muted" style="font-size:.72rem">Description</th>
                                        <th style="width:95px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="factor in root.children" :key="factor.id">
                                        <td>
                                            <code class="text-primary fw-bold" style="font-size:.75rem">
                                                {{ factor.code }}
                                            </code>
                                        </td>
                                        <td class="fw-semibold small">{{ factor.label }}</td>
                                        <td>
                                            <span v-if="factor.appetite"
                                                  class="badge rounded-pill px-2 py-1"
                                                  style="cursor:pointer"
                                                  :style="{ background:factor.appetite.color+'22', color:factor.appetite.color, border:`1px solid ${factor.appetite.color}55`, fontSize:'.72rem' }"
                                                  @click="openAppetitePanel(factor)">
                                                <i class="ti ti-flame me-1" style="font-size:.62rem"></i>
                                                {{ factor.appetite.label }}
                                            </span>
                                            <button v-else
                                                    class="btn btn-sm btn-outline-secondary py-0 px-2"
                                                    style="font-size:.7rem"
                                                    @click="openAppetitePanel(factor)">
                                                <i class="ti ti-flame-off me-1"></i>Assigner
                                            </button>
                                        </td>
                                        <td>
                                            <span v-if="factor.description" class="text-muted"
                                                  style="font-size:.68rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden">
                                                {{ factor.description }}
                                            </span>
                                            <span v-else class="text-muted opacity-40" style="font-size:.68rem">—</span>
                                        </td>
                                        <td class="text-end pe-2">
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-warning" title="Appétence"
                                                        @click="openAppetitePanel(factor)">
                                                    <i class="ti ti-flame"></i>
                                                </button>
                                                <button class="btn btn-outline-primary" title="Modifier"
                                                        @click="openEdit(factor)">
                                                    <i class="ti ti-pencil"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" title="Supprimer"
                                                        @click="deleteNode(factor)">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>

        <!-- ════ MODAL : Ajout / Édition facteur ════ -->
        <Teleport to="body">
            <div v-if="showModal" class="modal fade show d-block" tabindex="-1"
                 style="background:rgba(0,0,0,.45)">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i :class="`ti ${editMode ? 'ti-pencil' : 'ti-plus'} me-2`"></i>
                                {{ editMode ? 'Modifier le facteur' : 'Nouveau facteur de risque' }}
                            </h5>
                            <button class="btn-close" @click="showModal = false"></button>
                        </div>
                        <div class="modal-body">
                            <div v-if="!editMode && parentCtx" class="alert alert-info py-2 mb-3 small">
                                <i class="ti ti-sitemap me-1"></i>
                                Sous <strong>{{ parentCtx.code }}</strong> — {{ parentCtx.label }}
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    Libellé <span class="text-danger">*</span>
                                </label>
                                <input v-model="form.label" type="text" class="form-control"
                                       :class="{ 'is-invalid': errors.label }"
                                       placeholder="Ex : Défaillance des processus internes" />
                                <div v-if="errors.label" class="invalid-feedback">{{ errors.label[0] }}</div>
                            </div>
                            <div>
                                <label class="form-label fw-semibold">Description</label>
                                <textarea v-model="form.description" class="form-control" rows="3"
                                          placeholder="Optionnel…"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" @click="showModal = false">Annuler</button>
                            <button class="btn btn-primary" :disabled="loading" @click="submitNomenclature">
                                <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                                {{ editMode ? 'Enregistrer' : 'Créer' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ════ OFFCANVAS : Assignation appétence + description IA ════ -->
        <Teleport to="body">
            <!-- Backdrop cliquable uniquement quand le panel est ouvert -->
            <div v-if="showAppetitePanel"
                 class="offcanvas-backdrop fade show"
                 style="z-index:1040"
                 @click="closeAppetitePanel"></div>

            <!-- Offcanvas conditionnel via v-if : absent du DOM si fermé -->
            <div v-if="showAppetitePanel"
                 class="offcanvas offcanvas-end show"
                 style="width:420px; visibility:visible; z-index:1045">
                <div class="offcanvas-header border-bottom">
                    <h5 class="offcanvas-title">
                        <i class="ti ti-flame me-2 text-warning"></i>Assigner une appétence
                    </h5>
                    <button class="btn-close" @click="closeAppetitePanel"></button>
                </div>
                <div class="offcanvas-body d-flex flex-column gap-3 py-3">

                    <!-- Facteur sélectionné -->
                    <div v-if="appetiteNode"
                         class="p-3 rounded-2 border"
                         :style="{ background: typeColor(appetiteNode.type_code)+'0d', borderColor: typeColor(appetiteNode.type_code)+'44' }">
                        <code class="fw-bold" :style="{ color: typeColor(appetiteNode.type_code) }">
                            {{ appetiteNode.code }}
                        </code>
                        <span class="ms-2 fw-semibold small">{{ appetiteNode.label }}</span>
                    </div>

                    <!-- Choix du niveau -->
                    <div>
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-2">
                            Niveau d'appétence
                        </label>

                        <div v-if="!activeAppetites.length"
                             class="alert alert-warning py-2 small d-flex align-items-center gap-2">
                            <i class="ti ti-alert-triangle"></i>
                            Aucun niveau.
                            <button class="btn btn-sm btn-warning py-0"
                                    @click="closeAppetitePanel(); showAptPanel=true; openAptCreate()">
                                Créer
                            </button>
                        </div>

                        <div class="d-flex flex-column gap-2">
                            <!-- Option "aucune" -->
                            <label class="d-flex align-items-center gap-2 px-3 py-2 rounded border"
                                   :style="{ cursor:'pointer', background: selectedAppetiteId===null ? '#f8f9fa':'#fff' }">
                                <input type="radio" class="form-check-input mt-0"
                                       :checked="selectedAppetiteId === null"
                                       @change="onAppetiteChange(null)" />
                                <i class="ti ti-flame-off text-muted"></i>
                                <span class="small text-muted">Aucune appétence</span>
                            </label>

                            <!-- Options -->
                            <label v-for="apt in activeAppetites" :key="apt.id"
                                   class="d-flex align-items-center gap-2 px-3 py-2 rounded border"
                                   :style="{
                                       cursor: 'pointer',
                                       borderColor: selectedAppetiteId===apt.id ? apt.color : '#dee2e6',
                                       background: selectedAppetiteId===apt.id ? apt.color+'18' : '#fff',
                                   }">
                                <input type="radio" class="form-check-input mt-0"
                                       :checked="selectedAppetiteId === apt.id"
                                       @change="onAppetiteChange(apt.id)" />
                                <span class="rounded-circle flex-shrink-0"
                                      :style="{ width:'10px', height:'10px', background:apt.color, display:'inline-block' }">
                                </span>
                                <div class="flex-grow-1">
                                    <span class="fw-semibold small" :style="{ color: apt.color }">
                                        {{ apt.label }}
                                    </span>
                                    <small v-if="apt.description" class="text-muted d-block"
                                           style="font-size:.67rem">{{ apt.description }}</small>
                                </div>
                                <span class="text-muted" style="font-size:.7rem">
                                    {{ apt.score_min }}–{{ apt.score_max }}
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Zone description + indicateur IA -->
                    <div v-if="selectedAppetiteId !== null">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label fw-semibold small mb-0">
                                Description de l'appétence
                            </label>
                            <!-- Bouton Régénérer (toujours disponible si un niveau est choisi) -->
                            <button class="btn btn-sm btn-outline-primary py-0 px-2"
                                    style="font-size:.72rem"
                                    :disabled="aiLoading"
                                    @click="generateDescription">
                                <span v-if="aiLoading"
                                      class="spinner-border spinner-border-sm me-1"
                                      style="width:.7rem;height:.7rem"></span>
                                <i v-else class="ti ti-refresh me-1"></i>
                                {{ aiLoading ? 'Génération…' : 'Régénérer' }}
                            </button>
                        </div>

                        <!-- Skeleton pendant génération IA -->
                        <div v-if="aiLoading" class="rounded border p-2" style="background:#f8f9fb">
                            <div class="d-flex align-items-center gap-2 text-muted small mb-2">
                                <span class="spinner-border spinner-border-sm text-primary"></span>
                                <span>L'IA rédige la description…</span>
                            </div>
                            <div class="placeholder-glow">
                                <span class="placeholder col-12 mb-1 rounded" style="height:8px"></span>
                                <span class="placeholder col-10 mb-1 rounded" style="height:8px"></span>
                                <span class="placeholder col-8 rounded" style="height:8px"></span>
                            </div>
                        </div>

                        <textarea v-else
                                  v-model="appetiteDescription"
                                  class="form-control form-control-sm" rows="5"
                                  :placeholder="`Décrivez l'appétence « ${selectedAppetite?.label ?? '…'} » pour ce facteur…`">
                        </textarea>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex gap-2 pt-1 border-top mt-auto">
                        <button class="btn btn-secondary flex-grow-1"
                                @click="closeAppetitePanel">
                            Annuler
                        </button>
                        <button class="btn btn-warning flex-grow-1"
                                :disabled="appetiteLoading || aiLoading"
                                @click="saveAppetite">
                            <span v-if="appetiteLoading" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="ti ti-check me-1"></i>
                            Enregistrer
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ════ OFFCANVAS : CRUD niveaux d'appétence ════ -->
        <Teleport to="body">
            <div v-if="showAptPanel"
                 class="offcanvas-backdrop fade show"
                 style="z-index:1040"
                 @click="showAptPanel = false"></div>

            <div v-if="showAptPanel"
                 class="offcanvas offcanvas-end show"
                 style="width:460px; visibility:visible; z-index:1045">
                <div class="offcanvas-header border-bottom">
                    <h5 class="offcanvas-title">
                        <i class="ti ti-flame me-2 text-warning"></i>Niveaux d'appétence
                    </h5>
                    <button class="btn-close" @click="showAptPanel = false"></button>
                </div>
                <div class="offcanvas-body py-3 d-flex flex-column gap-4">

                    <!-- Liste existante -->
                    <div>
                        <h6 class="text-muted fw-semibold small text-uppercase mb-2">
                            Niveaux configurés ({{ appetites.length }})
                        </h6>

                        <div v-if="!appetites.length" class="text-center py-3 text-muted small">
                            <i class="ti ti-flame-off d-block fs-2 mb-1 opacity-25"></i>
                            Aucun niveau — créez-en un ci-dessous
                        </div>

                        <div class="d-flex flex-column gap-2">
                            <div v-for="apt in appetites" :key="apt.id"
                                 class="d-flex align-items-start gap-2 p-2 rounded border bg-white">
                                <span class="rounded-circle flex-shrink-0 mt-1"
                                      :style="{ width:'12px', height:'12px', background:apt.color, display:'inline-block' }">
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-semibold small" :style="{ color:apt.color }">
                                            {{ apt.label }}
                                        </span>
                                        <code class="text-muted" style="font-size:.65rem">{{ apt.code }}</code>
                                        <span class="badge bg-light text-muted border rounded-pill ms-auto"
                                              style="font-size:.65rem">
                                            {{ apt.score_min }}–{{ apt.score_max }}
                                        </span>
                                    </div>
                                    <small v-if="apt.description" class="text-muted"
                                           style="font-size:.67rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden">
                                        {{ apt.description }}
                                    </small>
                                </div>
                                <div class="btn-group btn-group-sm flex-shrink-0">
                                    <button class="btn btn-outline-primary" @click="openAptEdit(apt)">
                                        <i class="ti ti-pencil"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" @click="deleteApt(apt)">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulaire création / édition -->
                    <div class="border rounded-2 p-3" style="background:#f8f9fb">
                        <h6 class="fw-semibold mb-3">
                            <i :class="`ti ${aptEditMode ? 'ti-pencil' : 'ti-plus'} me-1 text-warning`"></i>
                            {{ aptEditMode ? 'Modifier' : 'Nouveau niveau' }}
                        </h6>

                        <!-- Presets rapides (création uniquement) -->
                        <div v-if="!aptEditMode" class="mb-3">
                            <label class="form-label fw-semibold small">Démarrer depuis un preset</label>
                            <div class="d-flex flex-wrap gap-1">
                                <button v-for="p in PRESETS" :key="p.code" type="button"
                                        class="btn btn-sm"
                                        :style="{ background:p.color+'22', color:p.color, border:`1px solid ${p.color}55` }"
                                        @click="applyPreset(p)">
                                    {{ p.label }}
                                </button>
                            </div>
                        </div>

                        <div v-if="!aptEditMode" class="mb-2">
                            <label class="form-label fw-semibold small">
                                Code <span class="text-danger">*</span>
                            </label>
                            <input v-model="aptForm.code" type="text"
                                   class="form-control form-control-sm"
                                   :class="{ 'is-invalid': aptErrors.code }"
                                   placeholder="Ex : AVERSE" style="text-transform:uppercase" />
                            <div v-if="aptErrors.code" class="invalid-feedback">{{ aptErrors.code[0] }}</div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold small">
                                Label <span class="text-danger">*</span>
                            </label>
                            <input v-model="aptForm.label" type="text"
                                   class="form-control form-control-sm"
                                   :class="{ 'is-invalid': aptErrors.label }"
                                   placeholder="Ex : Averse" />
                            <div v-if="aptErrors.label" class="invalid-feedback">{{ aptErrors.label[0] }}</div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold small">Couleur</label>
                            <div class="d-flex align-items-center gap-2">
                                <input v-model="aptForm.color" type="color"
                                       class="form-control form-control-color p-0 flex-shrink-0"
                                       style="width:36px;height:28px" />
                                <input v-model="aptForm.color" type="text"
                                       class="form-control form-control-sm font-monospace" maxlength="7" />
                                <span class="badge rounded-pill flex-shrink-0"
                                      :style="{ background:aptForm.color+'22', color:aptForm.color, border:`1px solid ${aptForm.color}55`, fontSize:'.72rem' }">
                                    {{ aptForm.label || 'Aperçu' }}
                                </span>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Score min</label>
                                <input v-model.number="aptForm.score_min" type="number"
                                       class="form-control form-control-sm" min="0" />
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Score max</label>
                                <input v-model.number="aptForm.score_max" type="number"
                                       class="form-control form-control-sm" :min="aptForm.score_min" />
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Tolérance <span class="text-danger">*</span></label>
                            <textarea v-model="aptForm.description"
                                      class="form-control form-control-sm" rows="2"
                                      placeholder="Ex : Tolérance nulle, contrôles maximaux obligatoires.">
                            </textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button v-if="aptEditMode" class="btn btn-sm btn-outline-secondary"
                                    @click="aptForm = defApt(); aptEditMode = false">
                                <i class="ti ti-x me-1"></i>Annuler
                            </button>
                            <button class="btn btn-sm btn-warning flex-grow-1"
                                    :disabled="aptLoading" @click="submitApt">
                                <span v-if="aptLoading" class="spinner-border spinner-border-sm me-1"></span>
                                <i v-else :class="`ti ${aptEditMode ? 'ti-check' : 'ti-plus'} me-1`"></i>
                                {{ aptEditMode ? 'Enregistrer' : 'Créer' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

    </VerticalLayout>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: all .2s ease; overflow: hidden }
.slide-enter-from, .slide-leave-to       { opacity:0; max-height:0 }
.slide-enter-to, .slide-leave-from       { opacity:1; max-height:2000px }
</style>