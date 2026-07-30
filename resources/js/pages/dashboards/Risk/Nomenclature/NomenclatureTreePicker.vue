<script setup lang="ts">
import axios from 'axios'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

interface NomNode {
    id: number
    code: string
    label: string
    level: number
    type_code: string
    type_label?: string
    type_color?: string
    type_icon?: string
    children?: NomNode[]
}

const props = defineProps<{
    modelValue: number[]
    placeholder?: string
    disabled?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', val: number[]): void
}>()

const tree      = ref<NomNode[]>([])
const loading   = ref(false)
const open      = ref(false)
const pickerRef = ref<HTMLElement | null>(null)

// -----------------------------------------------------------------------
// CORRECTION : ref<Set> n'est pas réactif en Vue 3 pour .has() dans le
// template. On utilise un Record<number, boolean> à la place.
// -----------------------------------------------------------------------
const expanded = ref<Record<number, boolean>>({})

onMounted(async () => {
    await loadTree()
    document.addEventListener('click', onClickOutside)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', onClickOutside)
})

const onClickOutside = (e: MouseEvent) => {
    if (pickerRef.value && !pickerRef.value.contains(e.target as Node)) {
        open.value = false
    }
}

const loadTree = async () => {
    loading.value = true
    try {
        const { data } = await axios.get(route('risk.core.nomenclature.tree'))
        tree.value = data
        // Auto-expand toutes les racines au chargement
        data.forEach((root: NomNode) => {
            expanded.value[root.id] = true
        })
    } finally {
        loading.value = false
    }
}

// Map plat pour lookup rapide (chip display)
const flatMap = computed(() => {
    const map: Record<number, NomNode> = {}
    const flatten = (nodes: NomNode[]) => {
        nodes.forEach((n) => {
            map[n.id] = n
            if (n.children) flatten(n.children)
        })
    }
    flatten(tree.value)
    return map
})

const selectedNodes = computed(() =>
    props.modelValue.map((id) => flatMap.value[id]).filter(Boolean)
)

const isSelected   = (id: number): boolean => props.modelValue.includes(id)
const isExpanded   = (id: number): boolean => !!expanded.value[id]

const toggleExpand = (id: number) => {
    expanded.value[id] = !expanded.value[id]
}

const toggleSelect = (id: number) => {
    const current = [...props.modelValue]
    const idx = current.indexOf(id)
    if (idx === -1) {
        emit('update:modelValue', [...current, id])
    } else {
        current.splice(idx, 1)
        emit('update:modelValue', current)
    }
}

const removeChip = (id: number) => {
    emit('update:modelValue', props.modelValue.filter((v) => v !== id))
}

const typeColor = (typeCode: string): string =>
    ({ RC: '#4361ee', RF: '#e63946', RS: '#7209b7', RO: '#f77f00' }[typeCode] ?? '#6c757d')
</script>

<template>
    <!--
        CORRECTION : position:relative sur le wrapper pour que le dropdown
        en position:absolute flotte par-dessus le contenu sans pousser la page.
    -->
    <div
        ref="pickerRef"
        class="nom-picker"
        :class="{ 'nom-picker--disabled': disabled }"
        style="position: relative;"
    >
        <!-- Chips des sélections -->
        <div v-if="selectedNodes.length" class="d-flex flex-wrap gap-1 mb-2">
            <span
                v-for="node in selectedNodes"
                :key="node.id"
                class="nom-chip d-inline-flex align-items-center gap-1 px-2 py-1"
                :style="{
                    background:   typeColor(node.type_code) + '20',
                    color:        typeColor(node.type_code),
                    border:       `1px solid ${typeColor(node.type_code)}60`,
                    borderRadius: '6px',
                    fontSize:     '0.78rem',
                }"
            >
                <code style="font-size: 0.7rem; font-weight: 600;">{{ node.code }}</code>
                <span>{{ node.label }}</span>
                <!--
                    CORRECTION : btn-close natif BS est un SVG noir masqué par filtre.
                    On le remplace par une icône Tabler pour rester visible sur fond coloré.
                -->
                <button
                    v-if="!disabled"
                    type="button"
                    class="nom-chip__close d-inline-flex align-items-center justify-content-center p-0 border-0"
                    :style="{ background: 'transparent', color: 'inherit', cursor: 'pointer', lineHeight: 1 }"
                    @click.stop="removeChip(node.id)"
                >
                    <i class="ti ti-x" style="font-size: 0.65rem;"></i>
                </button>
            </span>
        </div>

        <!-- Bouton déclencheur -->
        <button
            type="button"
            class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-between text-start"
            :disabled="disabled"
            @click="open = !open"
        >
            <span v-if="!selectedNodes.length" class="text-muted small">
                {{ placeholder ?? 'Sélectionner des nomenclatures...' }}
            </span>
            <span v-else class="small">
                {{ selectedNodes.length }} nomenclature(s) sélectionnée(s)
            </span>
            <i :class="`ti ${open ? 'ti-chevron-up' : 'ti-chevron-down'} text-muted ms-2`"></i>
        </button>

        <!-- Dropdown arborescent — position:absolute pour flotter au-dessus -->
        <Transition name="fade-drop">
            <div
                v-if="open"
                class="nom-picker__dropdown border rounded-2 bg-white shadow"
                style="
                    position:   absolute;
                    top:        calc(100% + 4px);
                    left:       0;
                    width:      100%;
                    max-height: 380px;
                    overflow-y: auto;
                    z-index:    1060;
                "
            >
                <!-- Chargement -->
                <div v-if="loading" class="text-muted small py-4 text-center">
                    <span class="spinner-border spinner-border-sm me-2"></span>Chargement...
                </div>

                <template v-else>
                    <div v-for="root in tree" :key="root.id">

                        <!-- Racine (niveau 1) -->
                        <div
                            class="d-flex align-items-center border-bottom px-3 py-2 root-row"
                            :style="{ background: typeColor(root.type_code) + '0d' }"
                            @click="toggleExpand(root.id)"
                        >
                            <i
                                :class="`${root.type_icon ?? 'ti ti-folder'} small me-2`"
                                :style="{ color: root.type_color ?? typeColor(root.type_code) }"
                            ></i>
                            <span
                                class="fw-semibold small flex-grow-1"
                                :style="{ color: root.type_color ?? typeColor(root.type_code) }"
                            >
                                {{ root.code }} — {{ root.type_label }}
                            </span>
                            <div class="form-check mb-0 me-2" @click.stop>
                                <input
                                    :id="`np-root-${root.id}`"
                                    type="checkbox"
                                    class="form-check-input"
                                    :checked="isSelected(root.id)"
                                    @change="toggleSelect(root.id)"
                                />
                            </div>
                            <i
                                :class="`ti ${isExpanded(root.id) ? 'ti-chevron-down' : 'ti-chevron-right'} text-muted`"
                                style="font-size: 0.75rem;"
                            ></i>
                        </div>

                        <!-- Enfants niveau 2 -->
                        <template v-if="isExpanded(root.id)">
                            <div v-for="child in root.children" :key="child.id">

                                <div
                                    class="d-flex align-items-center border-bottom px-4 py-2 np-row"
                                    @click="toggleExpand(child.id)"
                                >
                                    <i class="ti ti-folder-open text-muted small me-2"></i>
                                    <code class="small text-primary me-2 fw-bold">{{ child.code }}</code>
                                    <span class="small flex-grow-1">{{ child.label }}</span>
                                    <div class="form-check mb-0 me-2" @click.stop>
                                        <input
                                            :id="`np-${child.id}`"
                                            type="checkbox"
                                            class="form-check-input"
                                            :checked="isSelected(child.id)"
                                            @change="toggleSelect(child.id)"
                                        />
                                    </div>
                                    <i
                                        v-if="child.children?.length"
                                        :class="`ti ${isExpanded(child.id) ? 'ti-chevron-down' : 'ti-chevron-right'} text-muted`"
                                        style="font-size: 0.75rem;"
                                    ></i>
                                </div>

                                <!-- Enfants niveau 3 -->
                                <template v-if="isExpanded(child.id)">
                                    <div
                                        v-for="gc in child.children"
                                        :key="gc.id"
                                        class="d-flex align-items-center border-bottom px-5 py-2 np-row"
                                        style="background: #fafafa;"
                                    >
                                        <i class="ti ti-corner-down-right text-muted small me-2"></i>
                                        <code class="small text-success me-2 fw-bold">{{ gc.code }}</code>
                                        <span class="small flex-grow-1">{{ gc.label }}</span>
                                        <div class="form-check mb-0">
                                            <input
                                                :id="`np-${gc.id}`"
                                                type="checkbox"
                                                class="form-check-input"
                                                :checked="isSelected(gc.id)"
                                                @change="toggleSelect(gc.id)"
                                            />
                                        </div>
                                    </div>
                                </template>

                            </div>
                        </template>

                    </div>
                </template>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.nom-picker--disabled {
    opacity: 0.6;
    pointer-events: none;
}

/* Hover racine */
.root-row {
    cursor: pointer;
    transition: filter 0.1s;
}
.root-row:hover {
    filter: brightness(0.97);
}

/* Hover lignes enfants */
.np-row {
    cursor: pointer;
    transition: background 0.1s;
}
.np-row:hover {
    background: #f0f2f8 !important;
}

/* Chip close button reset */
.nom-chip__close {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    opacity: 0.7;
    transition: opacity 0.1s;
}
.nom-chip__close:hover {
    opacity: 1;
}

/* Animation dropdown */
.fade-drop-enter-active,
.fade-drop-leave-active {
    transition: all 0.15s ease;
}
.fade-drop-enter-from,
.fade-drop-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
