<template>
  <VerticalLayout>
    <Head title="Types de liens – Référentiels" />

    <b-card>
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0"><i class="ti ti-link me-1 text-primary"></i>Types de liens (processus)</h5>
        <Link :href="route('process.core.process.settings.index')" class="btn btn-light btn-sm">
          <i class="ti ti-arrow-left"></i> Tous les référentiels
        </Link>
      </div>

      <div class="table-responsive">
        <b-table-simple hover class="table table-sm table-nowrap">
          <b-thead class="bg-light">
            <b-tr>
              <b-th>Code</b-th>
              <b-th>Libellé</b-th>
            </b-tr>
          </b-thead>
          <b-tbody>
            <b-tr v-for="it in items" :key="it.id">
              <b-td class="fw-semibold">{{ it.code }}</b-td>
              <b-td>{{ it.label }}</b-td>
            </b-tr>
            <b-tr v-if="!items || items.length===0">
              <b-td colspan="2" class="text-center text-muted py-3">Aucun enregistrement</b-td>
            </b-tr>
          </b-tbody>
        </b-table-simple>
      </div>
    </b-card>
  </VerticalLayout>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps<{ items: Array<any> }>()
</script>