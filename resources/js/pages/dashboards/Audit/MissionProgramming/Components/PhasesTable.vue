<!-- resources/js/Pages/Audit/MissionProgramming/Components/PhasesTable.vue -->
<template>
    <div v-if="phases && phases.length > 0" class="text-sm">
        <div v-for="phase in phases" :key="phase.phase_id" class="mb-1">
            <span class="font-medium text-gray-700">{{ phase.phase_code }}:</span>
            <span v-if="phase.auditeurs" class="text-gray-600 ml-1">
                {{ phase.auditeurs }}
            </span>
            <span v-else class="text-gray-400 ml-1 italic">Aucun</span>
            <span class="ml-1 text-xs text-gray-400">({{ phase.nb_auditeurs }})</span>
        </div>
    </div>
    <div v-else class="text-sm text-gray-400 italic">
        Aucune phase assignée
    </div>
</template>

<script>
export default {
    props: {
        phases: Array
    }
};
</script>