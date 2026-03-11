<template>
    <VerticalLayout>
        <PageTitle title="Indisponibilités" subtitle="Gestion des absences et jours fériés" />

        <!-- ALERTE SUCCÈS -->
        <b-alert
            v-model="showSuccess"
            variant="success"
            dismissible
            class="mb-3"
            fade
        >
            <div class="d-flex align-items-center">
                <i class="ti ti-circle-check fs-20 me-2"></i>
                <span>{{ page.props.flash?.success }}</span>
            </div>
        </b-alert>

        <!-- STATISTIQUES KPI - Version simple avec cartes Bootstrap -->
        <b-row class="mb-4">
            <b-col xl="3" md="6" v-for="(stat, idx) in kpiStats" :key="idx">
                <b-card no-body class="shadow-sm">
                    <b-card-body class="p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">{{ stat.title }}</p>
                                <h3 class="mb-0 fw-bold">{{ stat.value }}</h3>
                            </div>
                            <div :class="'bg-soft-' + stat.color + ' rounded p-2'">
                                <i :class="stat.icon + ' fs-22 text-' + stat.color"></i>
                            </div>
                        </div>
                        <div class="mt-2">
                            <span class="text-success" v-if="stat.change > 0">
                                <i class="ti ti-arrow-up"></i> {{ stat.change }}%
                            </span>
                            <span class="text-danger" v-else-if="stat.change < 0">
                                <i class="ti ti-arrow-down"></i> {{ Math.abs(stat.change) }}%
                            </span>
                            <span class="text-muted ms-1">vs mois dernier</span>
                        </div>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>

        <!-- ONGLETS PRINCIPAUX -->
        <b-card no-body class="mb-4">
            <b-card-header class="border-bottom p-0 bg-transparent">
                <b-nav tabs card-header class="border-bottom-0 m-0" role="tablist">
                    <b-nav-item
                        v-for="tab in tabs"
                        :key="tab.id"
                        :active="activeTab === tab.id"
                        @click="activeTab = tab.id"
                        class="cursor-pointer"
                    >
                        <i :class="tab.icon" class="me-1"></i>
                        {{ tab.name }}
                        <b-badge v-if="tab.id === 'auditor'" variant="primary" pill class="ms-1">
                            {{ auditorUnavailabilities.length }}
                        </b-badge>
                        <b-badge v-if="tab.id === 'global'" variant="info" pill class="ms-1">
                            {{ globalUnavailabilities.length }}
                        </b-badge>
                    </b-nav-item>
                </b-nav>
            </b-card-header>

            <b-card-body class="p-4">
                <!-- TAB 1: CALENDRIER ANNUEL -->
                <div v-show="activeTab === 'calendar'">
                    <b-row class="mb-4">
                        <b-col sm="6">
                            <div class="d-flex align-items-center gap-3">
                                <h4 class="header-title mb-0">{{ currentYear }}</h4>
                                <div class="btn-group">
                                    <b-button variant="soft-primary" size="sm" @click="prevYear">
                                        <i class="ti ti-chevron-left"></i>
                                    </b-button>
                                    <b-button variant="soft-primary" size="sm" @click="nextYear">
                                        <i class="ti ti-chevron-right"></i>
                                    </b-button>
                                </div>
                            </div>
                        </b-col>
                        <b-col sm="6">
                            <b-form-select
                                v-model="selectedAuditorId"
                                :options="auditorOptions"
                                size="sm"
                                class="w-auto float-end"
                            />
                        </b-col>
                    </b-row>

                    <!-- CALENDRIER 12 MOIS -->
                    <b-row>
                        <b-col lg="3" md="4" sm="6" class="mb-4" v-for="month in 12" :key="month">
                            <b-card no-body class="shadow-sm h-100">
                                <b-card-header class="bg-primary bg-gradient text-white text-center py-2">
                                    <h5 class="mb-0 fs-14">{{ getMonthName(month) }}</h5>
                                </b-card-header>
                                <b-card-body class="p-2">
                                    <div class="d-grid calendar-grid">
                                        <div
                                            v-for="d in 7"
                                            :key="'day-' + d"
                                            class="text-center text-muted fs-11 fw-semibold py-1"
                                        >
                                            {{ getDayName(d) }}
                                        </div>
                                        <div
                                            v-for="(day, i) in getDaysInMonth(month)"
                                            :key="'cell-' + month + '-' + i"
                                            class="calendar-cell text-center p-1"
                                            :class="getDayClasses(month, day)"
                                            :style="getDayStyle(month, day)"
                                            v-b-tooltip.hover :title="getDayTooltip(month, day)"
                                        >
                                            <span v-if="day" class="fw-medium">{{ day }}</span>
                                        </div>
                                    </div>
                                </b-card-body>
                            </b-card>
                        </b-col>
                    </b-row>

                    <!-- LÉGENDE -->
                    <b-card class="mt-2">
                        <b-row>
                            <b-col md="6">
                                <h6 class="fw-semibold mb-3">
                                    <i class="ti ti-users me-1"></i> Auditeurs
                                </h6>
                                <div class="d-flex flex-wrap gap-3">
                                    <div v-for="aud in auditors" :key="'leg-' + aud.id" class="d-flex align-items-center">
                                        <span class="legend-dot me-1" :style="{ backgroundColor: getAuditorColor(aud.id) }"></span>
                                        <span class="fs-12">{{ aud.first_name }} {{ aud.last_name }}</span>
                                    </div>
                                </div>
                            </b-col>
                            <b-col md="6">
                                <h6 class="fw-semibold mb-3">
                                    <i class="ti ti-palette me-1"></i> Types
                                </h6>
                                <div class="d-flex flex-wrap gap-3">
                                    <div v-for="type in allTypes" :key="'leg-type-' + type.id" class="d-flex align-items-center">
                                        <span class="legend-dot me-1" :style="{ backgroundColor: type.color }"></span>
                                        <span class="fs-12">{{ type.icon }} {{ type.name }}</span>
                                    </div>
                                </div>
                            </b-col>
                        </b-row>
                    </b-card>
                </div>

                <!-- TAB 2: AUDITEURS -->
                <div v-show="activeTab === 'auditor'">
                    <b-card no-body class="border mb-4">
                        <b-card-header class="bg-light">
                            <h5 class="mb-0"><i class="ti ti-plus me-1"></i> Nouvelle indisponibilité</h5>
                        </b-card-header>
                        <b-card-body>
                            <b-form @submit.prevent="submitAuditorForm">
                                <b-row>
                                    <b-col md="3">
                                        <b-form-group label="Auditeur" label-for="auditor_id">
                                            <b-form-select
                                                id="auditor_id"
                                                v-model="form.auditor.auditor_id"
                                                :options="auditorOptions"
                                                required
                                            />
                                        </b-form-group>
                                    </b-col>
                                    <b-col md="3">
                                        <b-form-group label="Type" label-for="auditor_type">
                                            <b-form-select
                                                id="auditor_type"
                                                v-model="form.auditor.type"
                                                :options="auditorTypeOptions"
                                                required
                                            />
                                        </b-form-group>
                                    </b-col>
                                    <b-col md="2">
                                        <b-form-group label="Début" label-for="auditor_start">
                                            <b-form-input
                                                id="auditor_start"
                                                v-model="form.auditor.date_start"
                                                type="date"
                                                required
                                            />
                                        </b-form-group>
                                    </b-col>
                                    <b-col md="2">
                                        <b-form-group label="Fin" label-for="auditor_end">
                                            <b-form-input
                                                id="auditor_end"
                                                v-model="form.auditor.date_end"
                                                type="date"
                                                required
                                            />
                                        </b-form-group>
                                    </b-col>
                                    <b-col md="2" class="d-flex align-items-end">
                                        <b-button type="submit" variant="primary" class="w-100">
                                            <i class="ti ti-plus me-1"></i> Ajouter
                                        </b-button>
                                    </b-col>
                                </b-row>
                            </b-form>
                        </b-card-body>
                    </b-card>

                    <!-- TABLEAU AUDITEURS -->
                    <b-card no-body>
                        <div class="table-responsive">
                            <b-table-simple bordered hover class="mb-0">
                                <b-thead class="bg-light">
                                    <b-tr>
                                        <b-th>Auditeur</b-th>
                                        <b-th>Type</b-th>
                                        <b-th>Début</b-th>
                                        <b-th>Fin</b-th>
                                        <b-th class="text-center">Jours</b-th>
                                        <b-th>Statut</b-th>
                                        <b-th class="text-center">Actions</b-th>
                                    </b-tr>
                                </b-thead>
                                <b-tbody>
                                    <b-tr v-if="!auditorUnavailabilities.length">
                                        <b-td colspan="7" class="text-center text-muted py-5">
                                            <i class="ti ti-inbox fs-30 d-block mb-2"></i>
                                            Aucune indisponibilité
                                        </b-td>
                                    </b-tr>
                                    <b-tr v-for="u in auditorUnavailabilities" :key="u.id" :variant="u.is_approved ? 'success' : ''">
                                        <b-td>
                                            <div class="d-flex align-items-center">
                                                <span class="me-2 auditor-dot" :style="{ backgroundColor: getAuditorColor(u.auditor_id) }"></span>
                                                <span class="fw-medium">{{ u.auditor?.first_name }} {{ u.auditor?.last_name }}</span>
                                            </div>
                                        </b-td>
                                        <b-td>
                                            <b-badge :style="{ backgroundColor: getTypeColor(u.type, 'auditor'), color: '#fff' }">
                                                {{ getTypeIcon(u.type, 'auditor') }} {{ getTypeName(u.type, 'auditor') }}
                                            </b-badge>
                                        </b-td>
                                        <b-td>{{ formatDate(u.date_start) }}</b-td>
                                        <b-td>{{ formatDate(u.date_end) }}</b-td>
                                        <b-td class="text-center fw-semibold">{{ calculateDays(u.date_start, u.date_end) }}</b-td>
                                        <b-td>
                                            <b-badge :variant="u.is_approved ? 'success' : 'warning'">
                                                <i :class="u.is_approved ? 'ti ti-circle-check' : 'ti ti-clock'"></i>
                                                {{ u.is_approved ? 'Approuvé' : 'En attente' }}
                                            </b-badge>
                                        </b-td>
                                        <b-td class="text-center">
                                            <b-button-group size="sm">
                                                <b-button
                                                    v-if="!u.is_approved"
                                                    variant="soft-success"
                                                    @click="approveAuditor(u.id)"
                                                    v-b-tooltip.hover
                                                    title="Approuver"
                                                >
                                                    <i class="ti ti-circle-check"></i>
                                                </b-button>
                                                <b-button
                                                    variant="soft-danger"
                                                    @click="deleteAuditor(u.id)"
                                                    v-b-tooltip.hover
                                                    title="Supprimer"
                                                >
                                                    <i class="ti ti-trash"></i>
                                                </b-button>
                                            </b-button-group>
                                        </b-td>
                                    </b-tr>
                                </b-tbody>
                            </b-table-simple>
                        </div>
                    </b-card>
                </div>

                <!-- TAB 3: JOURS FÉRIÉS -->
                <div v-show="activeTab === 'global'">
                    <b-card no-body class="border mb-4">
                        <b-card-header class="bg-light">
                            <h5 class="mb-0"><i class="ti ti-plus me-1"></i> Nouveau jour férié</h5>
                        </b-card-header>
                        <b-card-body>
                            <b-form @submit.prevent="submitGlobalForm">
                                <b-row>
                                    <b-col md="3">
                                        <b-form-group label="Nom" label-for="global_name">
                                            <b-form-input
                                                id="global_name"
                                                v-model="form.global.name"
                                                placeholder="Ex: Noël 2024"
                                                required
                                            />
                                        </b-form-group>
                                    </b-col>
                                    <b-col md="3">
                                        <b-form-group label="Type" label-for="global_type">
                                            <b-form-select
                                                id="global_type"
                                                v-model="form.global.type"
                                                :options="globalTypeOptions"
                                                required
                                            />
                                        </b-form-group>
                                    </b-col>
                                    <b-col md="2">
                                        <b-form-group label="Début" label-for="global_start">
                                            <b-form-input
                                                id="global_start"
                                                v-model="form.global.date_start"
                                                type="date"
                                                required
                                            />
                                        </b-form-group>
                                    </b-col>
                                    <b-col md="2">
                                        <b-form-group label="Fin" label-for="global_end">
                                            <b-form-input
                                                id="global_end"
                                                v-model="form.global.date_end"
                                                type="date"
                                                required
                                            />
                                        </b-form-group>
                                    </b-col>
                                    <b-col md="2" class="d-flex align-items-end">
                                        <b-button type="submit" variant="primary" class="w-100">
                                            <i class="ti ti-plus me-1"></i> Ajouter
                                        </b-button>
                                    </b-col>
                                </b-row>
                            </b-form>
                        </b-card-body>
                    </b-card>

                    <!-- TABLEAU JOURS FÉRIÉS -->
                    <b-card no-body>
                        <div class="table-responsive">
                            <b-table-simple bordered hover class="mb-0">
                                <b-thead class="bg-light">
                                    <b-tr>
                                        <b-th>Nom</b-th>
                                        <b-th>Type</b-th>
                                        <b-th>Début</b-th>
                                        <b-th>Fin</b-th>
                                        <b-th class="text-center">Jours</b-th>
                                        <b-th>Statut</b-th>
                                        <b-th class="text-center">Actions</b-th>
                                    </b-tr>
                                </b-thead>
                                <b-tbody>
                                    <b-tr v-if="!globalUnavailabilities.length">
                                        <b-td colspan="7" class="text-center text-muted py-5">
                                            <i class="ti ti-inbox fs-30 d-block mb-2"></i>
                                            Aucun jour férié
                                        </b-td>
                                    </b-tr>
                                    <b-tr v-for="u in globalUnavailabilities" :key="u.id">
                                        <b-td class="fw-medium">{{ u.name }}</b-td>
                                        <b-td>
                                            <b-badge :style="{ backgroundColor: getTypeColor(u.type, 'global'), color: '#fff' }">
                                                {{ getTypeIcon(u.type, 'global') }} {{ getTypeName(u.type, 'global') }}
                                            </b-badge>
                                        </b-td>
                                        <b-td>{{ formatDate(u.date_start) }}</b-td>
                                        <b-td>{{ formatDate(u.date_end) }}</b-td>
                                        <b-td class="text-center fw-semibold">{{ calculateDays(u.date_start, u.date_end) }}</b-td>
                                        <b-td>
                                            <b-badge :variant="u.is_active ? 'success' : 'secondary'">
                                                <i :class="u.is_active ? 'ti ti-circle-filled' : 'ti ti-circle'"></i>
                                                {{ u.is_active ? 'Actif' : 'Inactif' }}
                                            </b-badge>
                                        </b-td>
                                        <b-td class="text-center">
                                            <b-button
                                                variant="soft-danger"
                                                size="sm"
                                                @click="deleteGlobal(u.id)"
                                                v-b-tooltip.hover
                                                title="Supprimer"
                                            >
                                                <i class="ti ti-trash"></i>
                                            </b-button>
                                        </b-td>
                                    </b-tr>
                                </b-tbody>
                            </b-table-simple>
                        </div>
                    </b-card>
                </div>

                <!-- TAB 4: TYPES -->
                <div v-show="activeTab === 'types'">
                    <b-row>
                        <b-col md="6">
                            <b-card class="border h-100">
                                <b-card-header class="bg-light">
                                    <h5 class="mb-0"><i class="ti ti-plus me-1"></i> Type Global</h5>
                                    <small class="text-muted">Jours fériés et blocages</small>
                                </b-card-header>
                                <b-card-body>
                                    <b-form @submit.prevent="createType('global')">
                                        <b-form-group label="Nom" label-for="type_global_name">
                                            <b-form-input
                                                id="type_global_name"
                                                v-model="typeForm.global.name"
                                                placeholder="Ex: Grève"
                                                required
                                            />
                                        </b-form-group>
                                        <b-row>
                                            <b-col cols="6">
                                                <b-form-group label="Icône" label-for="type_global_icon">
                                                    <b-form-input
                                                        id="type_global_icon"
                                                        v-model="typeForm.global.icon"
                                                        placeholder="⚡"
                                                        maxlength="3"
                                                        required
                                                    />
                                                </b-form-group>
                                            </b-col>
                                            <b-col cols="6">
                                                <b-form-group label="Couleur" label-for="type_global_color">
                                                    <b-form-input
                                                        id="type_global_color"
                                                        v-model="typeForm.global.color"
                                                        type="color"
                                                        required
                                                    />
                                                </b-form-group>
                                            </b-col>
                                        </b-row>
                                        <b-button type="submit" variant="primary" class="w-100 mt-2">
                                            <i class="ti ti-plus me-1"></i> Créer
                                        </b-button>
                                    </b-form>
                                </b-card-body>
                            </b-card>
                        </b-col>

                        <b-col md="6">
                            <b-card class="border h-100">
                                <b-card-header class="bg-light">
                                    <h5 class="mb-0"><i class="ti ti-plus me-1"></i> Type Auditeur</h5>
                                    <small class="text-muted">Absences des auditeurs</small>
                                </b-card-header>
                                <b-card-body>
                                    <b-form @submit.prevent="createType('auditor')">
                                        <b-form-group label="Nom" label-for="type_auditor_name">
                                            <b-form-input
                                                id="type_auditor_name"
                                                v-model="typeForm.auditor.name"
                                                placeholder="Ex: Formation"
                                                required
                                            />
                                        </b-form-group>
                                        <b-row>
                                            <b-col cols="6">
                                                <b-form-group label="Icône" label-for="type_auditor_icon">
                                                    <b-form-input
                                                        id="type_auditor_icon"
                                                        v-model="typeForm.auditor.icon"
                                                        placeholder="📚"
                                                        maxlength="3"
                                                        required
                                                    />
                                                </b-form-group>
                                            </b-col>
                                            <b-col cols="6">
                                                <b-form-group label="Couleur" label-for="type_auditor_color">
                                                    <b-form-input
                                                        id="type_auditor_color"
                                                        v-model="typeForm.auditor.color"
                                                        type="color"
                                                        required
                                                    />
                                                </b-form-group>
                                            </b-col>
                                        </b-row>
                                        <b-button type="submit" variant="primary" class="w-100 mt-2">
                                            <i class="ti ti-plus me-1"></i> Créer
                                        </b-button>
                                    </b-form>
                                </b-card-body>
                            </b-card>
                        </b-col>
                    </b-row>

                    <!-- LISTE DES TYPES -->
                    <b-row class="mt-4">
                        <b-col md="6">
                            <b-card class="border">
                                <h6 class="fw-semibold mb-3">Types Globaux</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <b-badge
                                        v-for="t in globalTypes"
                                        :key="t.id"
                                        :style="{ backgroundColor: t.color, color: '#fff' }"
                                        class="p-2 fs-12"
                                    >
                                        {{ t.icon }} {{ t.name }}
                                        <span v-if="t.is_custom" class="badge bg-white text-dark ms-1">Perso</span>
                                    </b-badge>
                                </div>
                            </b-card>
                        </b-col>
                        <b-col md="6">
                            <b-card class="border">
                                <h6 class="fw-semibold mb-3">Types Auditeurs</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <b-badge
                                        v-for="t in auditorTypes"
                                        :key="t.id"
                                        :style="{ backgroundColor: t.color, color: '#fff' }"
                                        class="p-2 fs-12"
                                    >
                                        {{ t.icon }} {{ t.name }}
                                        <span v-if="t.is_custom" class="badge bg-white text-dark ms-1">Perso</span>
                                    </b-badge>
                                </div>
                            </b-card>
                        </b-col>
                    </b-row>
                </div>
            </b-card-body>
        </b-card>

        <!-- BOUTONS D'EXPORT EN BAS -->
        <b-row>
            <b-col cols="12">
                <b-card no-body class="border">
                    <b-card-body class="d-flex justify-content-end gap-2">
                        <b-button variant="soft-success" @click="exportExcel">
                            <i class="ti ti-file-spreadsheet me-1"></i> Excel
                        </b-button>
                        <b-button variant="soft-danger" @click="exportPdf">
                            <i class="ti ti-file-pdf me-1"></i> PDF
                        </b-button>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import { usePage, router } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import PageTitle from '@/components/PageTitle.vue'

const page = usePage()

const props = defineProps({
    globalUnavailabilities: { type: Array, default: () => [] },
    auditorUnavailabilities: { type: Array, default: () => [] },
    auditors: { type: Array, default: () => [] },
    globalTypes: { type: Array, default: () => [] },
    auditorTypes: { type: Array, default: () => [] },
})

const currentYear = ref(new Date().getFullYear())
const selectedAuditorId = ref(null)
const activeTab = ref('calendar')
const showSuccess = ref(!!page.props.flash?.success)

const form = ref({
    auditor: { auditor_id: null, type: '', date_start: '', date_end: '' },
    global: { name: '', type: '', date_start: '', date_end: '' }
})

const typeForm = ref({
    global: { name: '', icon: '⚡', color: '#dc2626' },
    auditor: { name: '', icon: '🏖️', color: '#15803d' }
})

const tabs = [
    { id: 'calendar', name: 'Calendrier', icon: 'ti ti-calendar' },
    { id: 'auditor', name: 'Auditeurs', icon: 'ti ti-users' },
    { id: 'global', name: 'Jours Fériés', icon: 'ti ti-flag' },
    { id: 'types', name: 'Types', icon: 'ti ti-palette' }
]

// Options pour selects
const auditorOptions = computed(() => {
    const options = [{ value: null, text: '📊 Tous les auditeurs' }]
    props.auditors.forEach(aud => {
        options.push({
            value: aud.id,
            text: `👤 ${aud.first_name} ${aud.last_name} (${aud.audit_id})`
        })
    })
    return options
})

const globalTypeOptions = computed(() => {
    const options = [{ value: '', text: '-- Sélectionner --' }]
    props.globalTypes.forEach(t => {
        options.push({ value: t.code, text: `${t.icon} ${t.name}` })
    })
    return options
})

const auditorTypeOptions = computed(() => {
    const options = [{ value: '', text: '-- Sélectionner --' }]
    props.auditorTypes.forEach(t => {
        options.push({ value: t.code, text: `${t.icon} ${t.name}` })
    })
    return options
})

// Statistiques KPI
const kpiStats = computed(() => [
    {
        title: 'Approuvées',
        value: props.auditorUnavailabilities.filter(u => u.is_approved).length,
        icon: 'ti ti-circle-check',
        change: 0,
        color: 'success'
    },
    {
        title: 'En attente',
        value: props.auditorUnavailabilities.filter(u => !u.is_approved).length,
        icon: 'ti ti-clock',
        change: 0,
        color: 'warning'
    },
    {
        title: 'Auditeurs',
        value: new Set(props.auditorUnavailabilities.map(u => u.auditor_id)).size,
        icon: 'ti ti-users',
        change: 0,
        color: 'info'
    },
    {
        title: 'Total',
        value: props.globalUnavailabilities.length + props.auditorUnavailabilities.length,
        icon: 'ti ti-calendar-stats',
        change: 0,
        color: 'primary'
    }
])

const allTypes = computed(() => [...props.globalTypes, ...props.auditorTypes])

const auditorColors = ['#2563eb', '#0891b2', '#7c3aed', '#dc2626', '#15803d', '#f97316', '#6366f1', '#ec4899']

// Fonctions calendrier
function getMonthName(m: number) {
    const months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']
    return months[m - 1]
}

function getDayName(d: number) {
    const days = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']
    return days[d - 1]
}

function getDaysInMonth(month: number) {
    const first = new Date(currentYear.value, month - 1, 1)
    const last = new Date(currentYear.value, month, 0)
    const start = first.getDay() === 0 ? 7 : first.getDay()
    const days = []
    for (let i = 1; i < start; i++) days.push(null)
    for (let i = 1; i <= last.getDate(); i++) days.push(i)
    return days
}

function dateToString(y: number, m: number, d: number) {
    return `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`
}

function isDateInRange(check: string, start: string, end: string) {
    const c = new Date(check).getTime()
    const s = new Date(start).getTime()
    const e = new Date(end).getTime()
    return c >= s && c <= e
}

function hasEvent(month: number, day: number) {
    if (!day) return false
    const date = dateToString(currentYear.value, month, day)
    return (
        props.globalUnavailabilities.some(u => isDateInRange(date, u.date_start, u.date_end) && u.is_active) ||
        props.auditorUnavailabilities.some(u => {
            if (selectedAuditorId.value && u.auditor_id !== selectedAuditorId.value) return false
            return isDateInRange(date, u.date_start, u.date_end)
        })
    )
}

function getDayStyle(month: number, day: number) {
    if (!day) return {}
    const date = dateToString(currentYear.value, month, day)

    const global = props.globalUnavailabilities.find(u => isDateInRange(date, u.date_start, u.date_end) && u.is_active)
    if (global) {
        const type = props.globalTypes.find(t => t.code === global.type)
        if (type) return { backgroundColor: type.color, color: '#fff' }
    }

    const auditor = props.auditorUnavailabilities.find(u => {
        if (selectedAuditorId.value && u.auditor_id !== selectedAuditorId.value) return false
        return isDateInRange(date, u.date_start, u.date_end)
    })
    if (auditor) {
        const type = props.auditorTypes.find(t => t.code === auditor.type)
        if (type) return { backgroundColor: type.color, color: '#fff' }
    }
    return {}
}

function getDayClasses(month: number, day: number) {
    if (!day) return ['empty']
    const date = dateToString(currentYear.value, month, day)
    const dayOfWeek = new Date(date).getDay()
    const classes = []
    if (hasEvent(month, day)) classes.push('bg-opacity-25')
    if (dayOfWeek === 0 || dayOfWeek === 6) classes.push('bg-light')
    return classes
}

function getDayTooltip(month: number, day: number) {
    if (!day) return ''
    const date = dateToString(currentYear.value, month, day)
    const global = props.globalUnavailabilities.find(u => isDateInRange(date, u.date_start, u.date_end) && u.is_active)
    const auditor = props.auditorUnavailabilities.find(u => {
        if (selectedAuditorId.value && u.auditor_id !== selectedAuditorId.value) return false
        return isDateInRange(date, u.date_start, u.date_end)
    })
    let text = `${day}/${month}/${currentYear.value}`
    if (global) text += `\n📌 ${global.name}`
    if (auditor) text += `\n👤 ${auditor.auditor?.first_name} ${auditor.auditor?.last_name}`
    return text
}

function getAuditorColor(auditorId: number) {
    return auditorColors[(auditorId - 1) % auditorColors.length]
}

function getTypeColor(typeCode: string, category: string) {
    const types = category === 'global' ? props.globalTypes : props.auditorTypes
    return types.find(t => t.code === typeCode)?.color || '#667eea'
}

function getTypeIcon(typeCode: string, category: string) {
    const types = category === 'global' ? props.globalTypes : props.auditorTypes
    return types.find(t => t.code === typeCode)?.icon || '📌'
}

function getTypeName(typeCode: string, category: string) {
    const types = category === 'global' ? props.globalTypes : props.auditorTypes
    return types.find(t => t.code === typeCode)?.name || typeCode
}

function formatDate(date: string) {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function calculateDays(start: string, end: string) {
    if (!start || !end) return 0
    const s = new Date(start)
    const e = new Date(end)
    return Math.ceil((e.getTime() - s.getTime()) / (1000 * 60 * 60 * 24)) + 1
}

function prevYear() { currentYear.value-- }
function nextYear() { currentYear.value++ }

// Actions CRUD
function submitAuditorForm() {
    router.post(route('param.projects.unavailabilities.store-auditor'), form.value.auditor, {
        onSuccess: () => {
            form.value.auditor = { auditor_id: null, type: '', date_start: '', date_end: '' }
        }
    })
}

function submitGlobalForm() {
    router.post(route('param.projects.unavailabilities.store-global'), form.value.global, {
        onSuccess: () => {
            form.value.global = { name: '', type: '', date_start: '', date_end: '' }
        }
    })
}

function createType(category: string) {
    const typeData = category === 'global' ? typeForm.value.global : typeForm.value.auditor
    router.post(route('param.projects.unavailabilities.create-type'), {
        category,
        ...typeData
    }, {
        onSuccess: () => {
            typeForm.value[category] = { 
                name: '', 
                icon: category === 'global' ? '⚡' : '🏖️', 
                color: category === 'global' ? '#dc2626' : '#15803d' 
            }
        }
    })
}

function approveAuditor(id: number) {
    router.post(route('param.projects.unavailabilities.approve-auditor', id))
}

function deleteAuditor(id: number) {
    if (confirm('Supprimer cette indisponibilité ?')) {
        router.delete(route('param.projects.unavailabilities.destroy-auditor', id))
    }
}

function deleteGlobal(id: number) {
    if (confirm('Supprimer ce jour férié ?')) {
        router.delete(route('param.projects.unavailabilities.destroy-global', id))
    }
}

function exportExcel() {
    window.location.href = route('param.projects.unavailabilities.export-excel')
}

function exportPdf() {
    window.location.href = route('param.projects.unavailabilities.export-pdf')
}

// Watch pour fermer l'alerte après 5s
watch(() => page.props.flash?.success, (val) => {
    if (val) {
        showSuccess.value = true
        setTimeout(() => {
            showSuccess.value = false
        }, 5000)
    }
})
</script>

<style scoped>
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
}

.calendar-cell {
    border-radius: 4px;
    cursor: default;
    transition: all 0.2s;
}

.calendar-cell:not(.empty):hover {
    transform: scale(1.1);
    z-index: 1;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.auditor-dot {
    width: 10px;
    height: 10px;
    border-radius: 4px;
    display: inline-block;
}

.legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 4px;
    display: inline-block;
}

.fs-11 { font-size: 11px; }
.fs-12 { font-size: 12px; }
.fs-14 { font-size: 14px; }
.fs-20 { font-size: 20px; }
.fs-22 { font-size: 22px; }
.fs-30 { font-size: 30px; }
</style>