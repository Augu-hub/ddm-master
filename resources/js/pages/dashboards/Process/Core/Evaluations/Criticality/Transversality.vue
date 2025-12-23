<template>
  <VerticalLayout>
    <Head title="Transversalité — Référentiel" />
    <b-card class="shadow-sm border-0">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-semibold mb-0">
          <i class="ti ti-arrows-diagonal text-primary me-2"></i> Échelle de transversalité
        </h5>
        <Link :href="route('process.core.process.evaluations.criticality.index')" class="btn btn-light btn-sm">
          <i class="ti ti-arrow-left"></i> Retour
        </Link>
      </div>

      <b-table-simple hover bordered small>
        <b-thead class="bg-light">
          <b-tr>
            <b-th class="text-center">Score</b-th>
            <b-th>Libellé</b-th>
            <b-th>Description</b-th>
            <b-th class="text-center">Action</b-th>
          </b-tr>
        </b-thead>
        <b-tbody>
          <b-tr v-for="(i, idx) in items" :key="idx">
            <b-td class="text-center fw-bold">{{ i.score }}</b-td>
            <b-td><input v-model="i.label" class="form-control form-control-sm" /></b-td>
            <b-td><textarea v-model="i.description" rows="1" class="form-control form-control-sm"></textarea></b-td>
            <b-td class="text-center">
              <b-button size="sm" variant="success" @click="updateItem(i)">
                <i class="ti ti-device-floppy"></i>
              </b-button>
            </b-td>
          </b-tr>

          <b-tr>
            <b-td colspan="4" class="text-center">
              <b-button size="sm" variant="outline-primary" @click="addItem">
                <i class="ti ti-plus me-1"></i> Ajouter un niveau
              </b-button>
            </b-td>
          </b-tr>
        </b-tbody>
      </b-table-simple>
    </b-card>
  </VerticalLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps<{ items: Array<any> }>()
const form = useForm({ score: null, label: '', description: '' })

const addItem = () => {
  const s = props.items.length + 1
  form.score = s
  form.label = 'Niveau ' + s
  form.description = ''
  form.post(route('process.core.process.evaluations.criticality.store', 'process_transversality_scales'), {
    preserveScroll: true,
    onSuccess: () => alert('✅ Nouveau niveau ajouté.'),
  })
}

const updateItem = (i) => {
  form.score = i.score
  form.label = i.label
  form.description = i.description
  form.post(route('process.core.process.evaluations.criticality.update', 'process_transversality_scales'), {
    preserveScroll: true,
    onSuccess: () => alert('✅ Niveau mis à jour !'),
  })
}
</script>
