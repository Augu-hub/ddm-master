<template>
  <VerticalLayout>
    <Head title="Modélisation BPMN" />

    <!-- Header -->
    <div class="page-header-form">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <b-breadcrumb class="modern-breadcrumb">
            <b-breadcrumb-item @click="$inertia.visit(route('dashboard'))">
              Tableau de bord
            </b-breadcrumb-item>
            <b-breadcrumb-item @click="$inertia.visit(route('process.core.modeling.index'))">
              Modélisation
            </b-breadcrumb-item>
            <b-breadcrumb-item active>Modélisation BPMN</b-breadcrumb-item>
          </b-breadcrumb>
          <h2 class="page-title mt-2">
            <i class="ti ti-brain me-2"></i>
            Modélisation BPMN
          </h2>
          <p class="text-white opacity-75 mb-0">
            Créez et modifiez vos processus métier avec la norme BPMN 2.0
          </p>
        </div>
        <b-button
          variant="light"
          size="lg"
          @click="$inertia.visit(route('process.core.modeling.bpmn.create'))"
        >
          <i class="ti ti-plus me-2"></i>
          Nouveau Processus BPMN
        </b-button>
      </div>
    </div>

    <!-- Tableau -->
    <div class="form-container-modern p-4">
      <b-table
        :items="processes.data"
        :fields="fields"
        responsive
        hover
        bordered
        class="modern-table"
      >
        <template #cell(code)="{ item }">
          <code class="text-primary">{{ item.code }}</code>
        </template>

        <template #cell(name)="{ item }">
          <strong>{{ item.name }}</strong>
        </template>

        <template #cell(activities_count)="{ item }">
          <b-badge variant="info">
            <i class="ti ti-activity me-1"></i>
            {{ item.activities_count }} activité{{ item.activities_count > 1 ? 's' : '' }}
          </b-badge>
        </template>

        <template #cell(created_at)="{ item }">
          {{ formatDate(item.created_at) }}
        </template>

        <template #cell(actions)="{ item }">
          <div class="text-end">
            <b-button
              size="sm"
              variant="outline-primary"
              class="me-2"
              @click="$inertia.visit(route('process.core.modeling.bpmn.edit', item.id))"
            >
              <i class="ti ti-edit"></i>
            </b-button>
            <b-button
              size="sm"
              variant="outline-danger"
              @click="confirmDelete(item)"
            >
              <i class="ti ti-trash"></i>
            </b-button>
          </div>
        </template>
      </b-table>

      <!-- Pagination -->
      <div class="d-flex justify-content-center mt-4">
        <b-pagination
          v-model="currentPage"
          :total-rows="processes.total"
          :per-page="processes.per_page"
          pills
          align="center"
          @change="changePage"
        />
      </div>
    </div>
  </VerticalLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import { ref, computed } from 'vue'

const props = defineProps({
  processes: Object,
})

const currentPage = computed({
  get: () => props.processes.current_page,
  set: (val) => changePage(val)
})

const fields = [
  { key: 'code', label: 'Code', sortable: true },
  { key: 'name', label: 'Nom du Processus', sortable: true },
  { key: 'activities_count', label: 'Activités', class: 'text-center' },
  { key: 'created_at', label: 'Créé le', sortable: true },
  { key: 'actions', label: 'Actions', class: 'text-end' },
]

function formatDate(dateString) {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function changePage(page) {
  router.get(route('process.core.modeling.bpmn.index'), { page }, { preserveState: true, preserveScroll: true })
}

function confirmDelete(process) {
  if (confirm(`Supprimer définitivement le processus "${process.name}" ?`)) {
    router.delete(route('process.core.modeling.bpmn.destroy', process.id), {
      onSuccess: () => {
        // La page sera rechargée par Inertia automatiquement
      }
    })
  }
}
</script>

<style scoped>
.page-header-form {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem;
  border-radius: 16px;
  color: white;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
  margin-bottom: 2rem;
}

.modern-breadcrumb {
  background: rgba(255, 255, 255, 0.1);
  padding: 0.5rem 1rem;
  border-radius: 8px;
}

.page-title {
  font-size: 1.75rem;
  font-weight: 700;
}

.modern-table {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.modern-table :deep(thead) {
  background: #f8f9fa;
}

.modern-table :deep(th) {
  font-weight: 600;
  border-bottom: 2px solid #dee2e6;
}
</style>