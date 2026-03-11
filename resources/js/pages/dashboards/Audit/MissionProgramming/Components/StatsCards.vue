<!-- resources/js/Pages/Audit/MissionProgramming/Components/StatsCards.vue -->
<template>
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <!-- Total missions -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                        <i class="fas fa-calendar-alt text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 truncate">Total missions</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ stats.total_missions }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Planifiées -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                        <i class="fas fa-clock text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 truncate">Planifiées</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ stats.missions_planifiees }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- En cours -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                        <i class="fas fa-spinner text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 truncate">En cours</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ stats.missions_en_cours }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Terminées -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                        <i class="fas fa-check-circle text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 truncate">Terminées</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ stats.missions_terminees }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Annulées -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                        <i class="fas fa-times-circle text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 truncate">Annulées</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ stats.missions_annulees }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        stats: Object
    }
};
</script>