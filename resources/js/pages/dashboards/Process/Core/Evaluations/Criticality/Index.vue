<template>
  <VerticalLayout>
    <Head title="Criticité — Vue d’ensemble" />

    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h4 class="fw-semibold mb-0"><i class="ti ti-flame text-danger me-2"></i>Échelles de criticité</h4>
        <p class="text-muted mb-0">Vue d’ensemble des référentiels de maturité, motricité, transversalité et poids stratégique.</p>
      </div>
    </div>

    <b-row>
      <b-col md="6" lg="6" class="mb-3" v-for="table in tables" :key="table.key">
        <b-card class="shadow-sm h-100">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-semibold m-0 text-primary">
              <i :class="table.icon + ' me-2'"></i>{{ table.label }}
            </h6>
            <Link :href="table.link" class="btn btn-sm btn-outline-primary">
              <i class="ti ti-eye"></i> Voir
            </Link>
          </div>

          <div class="table-responsive">
            <b-table-simple hover class="table table-sm table-nowrap">
              <b-thead class="bg-light">
                <b-tr>
                  <b-th class="text-center">Score</b-th>
                  <b-th>Libellé</b-th>
                  <b-th>Description</b-th>
                </b-tr>
              </b-thead>
              <b-tbody>
                <b-tr v-for="row in table.items" :key="row.score">
                  <b-td class="text-center fw-bold">{{ row.score }}</b-td>
                  <b-td>{{ row.label }}</b-td>
                  <b-td class="text-muted small">{{ row.description }}</b-td>
                </b-tr>
              </b-tbody>
            </b-table-simple>
          </div>
        </b-card>
      </b-col>
    </b-row>
  </VerticalLayout>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps<{
  maturity: Array<any>,
  motricity: Array<any>,
  transversality: Array<any>,
  strategic: Array<any>
}>()

const tables = [
  { key: 'maturity', label: 'Niveaux de maturité', icon: 'ti ti-chart-bar', link: route('process.core.process.evaluations.criticality.maturity'), items: props.maturity },
  { key: 'motricity', label: 'Niveaux de motricité', icon: 'ti ti-topology-star', link: route('process.core.process.evaluations.criticality.motricity'), items: props.motricity },
  { key: 'transversality', label: 'Niveaux de transversalité', icon: 'ti ti-arrows-join-2', link: route('process.core.process.evaluations.criticality.transversality'), items: props.transversality },
  { key: 'strategic', label: 'Poids stratégique', icon: 'ti ti-target-arrow', link: route('process.core.process.evaluations.criticality.strategic'), items: props.strategic },
]
</script>
