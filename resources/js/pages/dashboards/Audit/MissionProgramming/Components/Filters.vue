<!-- resources/js/Pages/Audit/MissionProgramming/Components/Filters.vue -->
<template>
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-4">
            <form @submit.prevent="applyFilters" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Recherche -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
                    <input type="text" 
                           v-model="form.search" 
                           placeholder="Code, libellé, FPM..."
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                </div>

                <!-- Statut -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                    <select v-model="form.status" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                        <option value="">Tous</option>
                        <option value="planifiee">Planifiée</option>
                        <option value="en_cours">En cours</option>
                        <option value="terminee">Terminée</option>
                        <option value="annulee">Annulée</option>
                    </select>
                </div>

                <!-- Date début -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date début ≥</label>
                    <input type="date" 
                           v-model="form.date_debut" 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                </div>

                <!-- Date fin -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date fin ≤</label>
                    <input type="date" 
                           v-model="form.date_fin" 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                </div>

                <!-- Boutons -->
                <div class="flex items-end space-x-2">
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
                        <i class="fas fa-filter mr-2"></i>
                        Filtrer
                    </button>
                    <button type="button" 
                            @click="resetFilters"
                            class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-md">
                        <i class="fas fa-redo mr-2"></i>
                        Reset
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        filters: Object
    },

    data() {
        return {
            form: {
                search: this.filters?.search || '',
                status: this.filters?.status || '',
                date_debut: this.filters?.date_debut || '',
                date_fin: this.filters?.date_fin || ''
            }
        };
    },

    methods: {
        applyFilters() {
            this.$emit('update-filters', this.form);
        },

        resetFilters() {
            this.form = {
                search: '',
                status: '',
                date_debut: '',
                date_fin: ''
            };
            this.$emit('update-filters', this.form);
        }
    }
};
</script>