<template>
  <VerticalLayout>
    <Head title="Criticité – Référentiels" />

    <b-row>
      <b-col md="6" class="mb-3">
        <b-card>
          <h5 class="mb-2"><i class="ti ti-list-details me-1 text-primary"></i>Critères</h5>
          <div class="table-responsive">
            <b-table-simple hover class="table table-sm table-nowrap">
              <b-thead class="bg-light">
                <b-tr>
                  <b-th>Code</b-th>
                  <b-th>Libellé</b-th>
                  <b-th class="text-center">Poids</b-th>
                  <b-th class="text-center">Ordre</b-th>
                </b-tr>
              </b-thead>
              <b-tbody>
                <b-tr v-for="c in criteria" :key="c.id">
                  <b-td class="fw-semibold">{{ c.code }}</b-td>
                  <b-td>{{ c.label }}</b-td>
                  <b-td class="text-center">{{ c.weight }}</b-td>
                  <b-td class="text-center">{{ c.sort }}</b-td>
                </b-tr>
                <b-tr v-if="!criteria || criteria.length===0">
                  <b-td colspan="4" class="text-center text-muted py-3">Aucun critère</b-td>
                </b-tr>
              </b-tbody>
            </b-table-simple>
          </div>
        </b-card>
      </b-col>

      <b-col md="6" class="mb-3">
        <b-card>
          <h5 class="mb-2"><i class="ti ti-flame me-1 text-danger"></i>Niveaux</h5>
          <div class="table-responsive">
            <b-table-simple hover class="table table-sm table-nowrap">
              <b-thead class="bg-light">
                <b-tr>
                  <b-th>Code</b-th>
                  <b-th>Libellé</b-th>
                  <b-th class="text-center">Poids</b-th>
                  <b-th>Couleur</b-th>
                </b-tr>
              </b-thead>
              <b-tbody>
                <b-tr v-for="l in levels" :key="l.id">
                  <b-td class="fw-semibold">{{ l.code }}</b-td>
                  <b-td>{{ l.label }}</b-td>
                  <b-td class="text-center">{{ l.weight }}</b-td>
                  <b-td>
                    <span class="badge" :style="{ background: l.color_hex || '#999' }">{{ l.color_hex || '—' }}</span>
                  </b-td>
                </b-tr>
                <b-tr v-if="!levels || levels.length===0">
                  <b-td colspan="4" class="text-center text-muted py-3">Aucun niveau</b-td>
                </b-tr>
              </b-tbody>
            </b-table-simple>
          </div>
        </b-card>
      </b-col>
    </b-row>

    <div class="mt-2">
      <Link :href="route('process.core.process.settings.index')" class="btn btn-light btn-sm">
        <i class="ti ti-arrow-left"></i> Tous les référentiels
      </Link>
    </div>
  </VerticalLayout>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps<{ criteria: Array<any>, levels: Array<any> }>()
</script>