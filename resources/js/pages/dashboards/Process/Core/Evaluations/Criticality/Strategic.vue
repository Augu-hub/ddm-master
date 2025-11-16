<template>
  <VerticalLayout>
    <Head title="Poids stratégique – Référentiel" />

    <b-card class="shadow-sm">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-semibold mb-0"><i class="ti ti-target-arrow text-warning me-1"></i> Poids stratégique</h5>
        <Link :href="route('process.core.process.evaluations.criticality.index')" class="btn btn-light btn-sm">
          <i class="ti ti-arrow-left"></i> Retour
        </Link>
      </div>

      <div class="table-responsive">
        <b-table-simple hover class="table table-sm table-nowrap align-middle">
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
              <b-td><input v-model="i.label" class="form-control form-control-sm border-0 bg-transparent fw-semibold" /></b-td>
              <b-td><textarea v-model="i.description" rows="1" class="form-control form-control-sm border-0 bg-transparent text-muted small"></textarea></b-td>
              <b-td class="text-center">
                <button class="btn btn-sm btn-outline-success" @click="submit(i)">
                  <i class="ti ti-check"></i>
                </button>
              </b-td>
            </b-tr>
          </b-tbody>
        </b-table-simple>
      </div>
    </b-card>
  </VerticalLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
const props = defineProps<{ items: Array<any> }>()
const form = useForm({ score: null, label: '', description: '' })
const submit = (item) => {
  form.score = item.score
  form.label = item.label
  form.description = item.description
  form.post(route('process.core.process.evaluations.criticality.update', 'process_strategic_weight_scales'), {
    preserveScroll: true,
    onSuccess: () => alert('✅ Poids stratégique mis à jour !')
  })
}
</script>
