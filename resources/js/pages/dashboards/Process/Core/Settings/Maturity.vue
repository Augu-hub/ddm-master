<template>
  <VerticalLayout>
    <Head title="Référentiel — Niveaux de maturité" />

    <b-row class="mb-2">
      <b-col>
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <i class="ti ti-chart-bar text-primary fs-5"></i>
            <h4 class="m-0 fw-semibold">Niveaux de maturité</h4>
          </div>
          <Link :href="route('process.core.process.settings.index')" class="btn btn-sm btn-light">
            <i class="ti ti-arrow-left"></i> Référentiels
          </Link>
        </div>
      </b-col>
    </b-row>

    <b-card no-body>
      <b-card-body class="p-0">
        <div class="table-responsive">
          <b-table-simple hover class="table table-sm mb-0 align-middle">
            <b-thead class="bg-light">
              <b-tr>
                <b-th>Code</b-th>
                <b-th>Libellé</b-th>
                <b-th>Sort</b-th>
                <b-th>Rang</b-th>
                <b-th>Min</b-th>
                <b-th>Max</b-th>
                <b-th>Couleur</b-th>
              </b-tr>
            </b-thead>
            <b-tbody>
              <b-tr v-for="it in items" :key="it.id">
                <b-td class="fw-semibold">{{ it.code }}</b-td>
                <b-td>{{ it.label }}</b-td>
                <b-td>{{ it.sort }}</b-td>
                <b-td>{{ it.rank }}</b-td>
                <b-td>{{ it.min_score }}</b-td>
                <b-td>{{ it.max_score }}</b-td>
                <b-td>
                  <span v-if="it.color" class="badge" :style="{ background: it.color, border:'1px solid #ddd' }">&nbsp;</span>
                  <span v-else class="text-muted">—</span>
                </b-td>
              </b-tr>
              <b-tr v-if="!items || items.length===0">
                <b-td colspan="7" class="text-center text-muted py-3">Aucun enregistrement.</b-td>
              </b-tr>
            </b-tbody>
          </b-table-simple>
        </div>
      </b-card-body>
    </b-card>
  </VerticalLayout>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
// route() fourni globalement par Ziggy
interface Item { id:number|string; code:string; label:string; sort:number; rank:number; min_score:number; max_score:number; color?:string|null }
const props = defineProps<{ items: Item[] }>()
</script>
