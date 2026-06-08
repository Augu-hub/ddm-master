<template>
    <VerticalLayout>
        <Head title="RISK — Configuration des matrices" />

        <b-row class="mb-3">
            <b-col>
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <i class="ti ti-matrix text-primary fs-4 me-2"></i>
                        <h4 class="d-inline-block fw-semibold">Configurations des matrices</h4>
                        <p class="text-muted small mt-1 mb-0">Gérez les matrices de criticité Impact × Fréquence</p>
                    </div>
                    <b-button variant="primary" size="sm" @click="showCreateModal = true">
                        <i class="ti ti-plus me-1"></i>Nouvelle configuration
                    </b-button>
                </div>
            </b-col>
        </b-row>

        <!-- Liste des configurations -->
        <b-row>
            <b-col v-for="config in configs" :key="config.id" lg="4" md="6" class="mb-3">
                <b-card :class="['config-card h-100', { 'config-card--active': config.is_active }]" no-body>
                    <div class="config-header" :style="config.is_active ? 'border-left:4px solid #22c55e' : ''">
                        <div class="d-flex align-items-center justify-content-between p-3">
                            <div>
                                <h6 class="mb-0 fw-semibold">{{ config.name }}</h6>
                                <span class="badge bg-light mt-1">
                                    <i class="ti ti-grid-dots me-1"></i>{{ config.matrix_label }}
                                </span>
                            </div>
                            <div v-if="config.is_active" class="active-badge">
                                <i class="ti ti-check-circle text-success"></i>
                                <span class="small text-success">Active</span>
                            </div>
                        </div>
                    </div>
                    <b-card-body class="p-3">
                        <div class="config-stats d-flex gap-3 mb-3">
                            <div class="stat-item">
                                <div class="stat-value">{{ config.impact_levels_count }}</div>
                                <div class="stat-label">Impacts</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">{{ config.frequency_levels_count }}</div>
                                <div class="stat-label">Fréquences</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">{{ config.criticality_zones_count }}</div>
                                <div class="stat-label">Zones</div>
                            </div>
                        </div>
                        <div v-if="config.description" class="text-muted small mb-3">
                            {{ trunc(config.description, 80) }}
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="status-badge" :class="config.is_complete ? 'status-complete' : 'status-incomplete'">
                                <i :class="config.is_complete ? 'ti ti-check' : 'ti ti-alert-circle'"></i>
                                {{ config.is_complete ? 'Complète' : 'Incomplète' }}
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" @click="editConfig(config)" title="Modifier">
                                    <i class="ti ti-edit"></i>
                                </button>
                                <button v-if="!config.is_active && config.is_complete" class="btn btn-outline-success" @click="activateConfig(config.id)" title="Activer">
                                    <i class="ti ti-check-circle"></i>
                                </button>
                                <button v-if="!config.is_active" class="btn btn-outline-danger" @click="deleteConfig(config.id, config.name)" title="Supprimer">
                                    <i class="ti ti-trash"></i>
                                </button>
                                <!-- URL directe au lieu de route() -->
                                <a :href="`/m/risk.core/matrix?config_id=${config.id}`" class="btn btn-outline-secondary" title="Voir la matrice">
                                    <i class="ti ti-eye"></i>
                                </a>
                            </div>
                        </div>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>

        <!-- Modal création -->
        <b-modal v-model="showCreateModal" title="Nouvelle configuration" size="md" hide-footer>
            <b-form @submit.prevent="createConfig">
                <div class="mb-3">
                    <label class="form-label">Nom de la configuration *</label>
                    <input type="text" v-model="newConfig.name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Taille de la matrice *</label>
                    <select v-model="newConfig.matrix_size" class="form-select">
                        <option value="3">3×3</option>
                        <option value="4">4×4</option>
                        <option value="5">5×5</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea v-model="newConfig.description" rows="2" class="form-control"></textarea>
                </div>
                <div class="d-flex gap-2 justify-content-end">
                    <b-button variant="light" @click="showCreateModal = false">Annuler</b-button>
                    <b-button type="submit" variant="primary">Créer</b-button>
                </div>
            </b-form>
        </b-modal>

        <!-- Modal édition -->
        <b-modal v-model="showEditModal" title="Modifier la configuration" size="md" hide-footer>
            <b-form @submit.prevent="updateConfig">
                <div class="mb-3">
                    <label class="form-label">Nom de la configuration *</label>
                    <input type="text" v-model="editConfigData.name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea v-model="editConfigData.description" rows="2" class="form-control"></textarea>
                </div>
                <div class="d-flex gap-2 justify-content-end">
                    <b-button variant="light" @click="showEditModal = false">Annuler</b-button>
                    <b-button type="submit" variant="primary">Enregistrer</b-button>
                </div>
            </b-form>
        </b-modal>
    </VerticalLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
    configs: {
        type: Array,
        default: () => []
    }
})

const configs = ref(props.configs || [])
const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingConfigId = ref(null)

const newConfig = ref({
    name: '',
    matrix_size: '5',
    description: ''
})

const editConfigData = ref({
    name: '',
    description: ''
})

const trunc = (str, n) => {
    if (!str) return ''
    return str.length > n ? str.slice(0, n) + '…' : str
}

const createConfig = async () => {
    try {
        const response = await axios.post('/m/risk.core/matrix-config', newConfig.value)
        showCreateModal.value = false
        newConfig.value = { name: '', matrix_size: '5', description: '' }
        await refreshConfigs()
    } catch (error) {
        console.error('Create error:', error)
        alert('Erreur lors de la création')
    }
}

const editConfig = (config) => {
    editingConfigId.value = config.id
    editConfigData.value = {
        name: config.name,
        description: config.description || ''
    }
    showEditModal.value = true
}

const updateConfig = async () => {
    try {
        await axios.put(`/m/risk.core/matrix-config/${editingConfigId.value}`, editConfigData.value)
        showEditModal.value = false
        await refreshConfigs()
    } catch (error) {
        console.error('Update error:', error)
        alert('Erreur lors de la mise à jour')
    }
}

const activateConfig = async (id) => {
    if (!confirm('Activer cette configuration ? Les autres seront désactivées.')) return
    try {
        await axios.post(`/m/risk.core/matrix-config/${id}/activate`)
        await refreshConfigs()
    } catch (error) {
        console.error('Activate error:', error)
        alert('Erreur lors de l\'activation')
    }
}

const deleteConfig = async (id, name) => {
    if (!confirm(`Supprimer la configuration "${name}" ?`)) return
    try {
        await axios.delete(`/m/risk.core/matrix-config/${id}`)
        await refreshConfigs()
    } catch (error) {
        console.error('Delete error:', error)
        alert('Erreur lors de la suppression')
    }
}

const refreshConfigs = async () => {
    try {
        const response = await axios.get('/m/risk.core/matrix-config')
        configs.value = response.data.props.configs || []
    } catch (error) {
        console.error('Refresh error:', error)
    }
}
</script>

<style scoped>
.config-card {
    transition: all .2s ease;
    border-radius: 12px;
    overflow: hidden;
}
.config-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,.1);
}
.config-card--active {
    background: linear-gradient(135deg, #f0fdf4 0%, #fff 100%);
}
.config-header {
    border-bottom: 1px solid #e5e7eb;
}
.active-badge {
    display: flex;
    align-items: center;
    gap: 4px;
}
.config-stats {
    text-align: center;
}
.stat-item {
    flex: 1;
    background: #f8fafc;
    border-radius: 8px;
    padding: 8px 4px;
}
.stat-value {
    font-size: 1.2rem;
    font-weight: 700;
    color: #1e293b;
}
.stat-label {
    font-size: .6rem;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: .03em;
}
.status-badge {
    font-size: .65rem;
    padding: 3px 8px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.status-complete {
    background: #dcfce7;
    color: #15803d;
}
.status-incomplete {
    background: #fee2e2;
    color: #b91c1c;
}
.btn-group-sm .btn {
    padding: 4px 8px;
    font-size: .7rem;
}
</style>