<template>
  <VerticalLayout>
    <Head title="Processus — Référentiels" />

    <b-row class="mb-2">
      <b-col>
        <div class="d-flex align-items-center gap-2">
          <i class="ti ti-adjustments text-primary fs-5"></i>
          <h5 class="m-0 fw-semibold">Référentiels du module Processus</h5>
          <small class="text-muted">Lecture seule — valeurs seedées</small>
        </div>
      </b-col>
    </b-row>

    <b-row class="g-3">
      <RefCard title="Échelles de maturité" :items="maturity" :cols="[
        {key:'code',label:'Code',class:'fw-semibold'},
        {key:'label',label:'Libellé'},
        {key:'rank',label:'Rang'},
        {key:'min_score',label:'Min'},
        {key:'max_score',label:'Max'},
        {key:'sort',label:'Tri'},
        {key:'color_hex',label:'Couleur'}
      ]" />

      <RefCard title="Niveaux de criticité" :items="criticality" :cols="[
        {key:'code',label:'Code',class:'fw-semibold'},
        {key:'label',label:'Libellé'},
        {key:'weight',label:'Poids'},
        {key:'sort',label:'Tri'},
        {key:'color_hex',label:'Couleur'}
      ]" />

      <RefCard title="RACI — Rôles" :items="raci" :cols="[
        {key:'code',label:'Code',class:'fw-semibold'},
        {key:'label',label:'Libellé'},
        {key:'description',label:'Description'}
      ]" />

      <RefCard title="IDEA — Axes" :items="idea_axes" :cols="[
        {key:'code',label:'Code',class:'fw-semibold'},
        {key:'label',label:'Libellé'}
      ]" />

      <RefCard title="KPI — Catégories" :items="kpi_cats" :cols="[
        {key:'code',label:'Code',class:'fw-semibold'},
        {key:'label',label:'Libellé'},
        {key:'sort',label:'Tri'}
      ]" />

      <RefCard title="Types de liens de processus" :items="link_types" :cols="[
        {key:'code',label:'Code',class:'fw-semibold'},
        {key:'label',label:'Libellé'}
      ]" />

      <RefCard title="Types de contrôles (activité)" :items="control_types" :cols="[
        {key:'code',label:'Code',class:'fw-semibold'},
        {key:'label',label:'Libellé'}
      ]" />
    </b-row>
  </VerticalLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  maturity: { type: Array, default: () => [] },
  criticality: { type: Array, default: () => [] },
  raci: { type: Array, default: () => [] },
  idea_axes: { type: Array, default: () => [] },
  kpi_cats: { type: Array, default: () => [] },
  link_types: { type: Array, default: () => [] },
  control_types: { type: Array, default: () => [] },
})
</script>

<script>
// composant local simple (on peut l’extraire si tu veux)
export default {
  components: {
    RefCard: {
      props: { title: String, items: Array, cols: Array },
      template: `
        <b-col md="6" xl="4">
          <b-card no-body class="h-100">
            <b-card-header class="py-2 d-flex align-items-center gap-2">
              <i class="ti ti-database text-primary"></i>
              <span class="fw-semibold">{{ title }}</span>
              <span class="ms-auto badge bg-light text-dark">{{ (items||[]).length }}</span>
            </b-card-header>
            <b-card-body class="p-0">
              <div class="table-responsive">
                <table class="table table-sm table-hover table-nowrap mb-0">
                  <thead class="table-light">
                    <tr>
                      <th v-for="c in cols" :key="c.key" class="text-muted fs-12 text-uppercase">{{ c.label }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row,idx) in items" :key="idx">
                      <td v-for="c in cols" :key="c.key" :class="c.class||''">
                        <span v-if="c.key==='color_hex'">
                          <span v-if="row[c.key]" class="d-inline-flex align-items-center gap-2">
                            <span class="rounded-circle d-inline-block" style="width:12px;height:12px" :style="{background: row[c.key]}"></span>
                            {{ row[c.key] }}
                          </span>
                          <span v-else>—</span>
                        </span>
                        <span v-else>{{ row[c.key] ?? '—' }}</span>
                      </td>
                    </tr>
                    <tr v-if="!items || items.length===0">
                      <td :colspan="cols.length" class="text-center text-muted py-3">Aucune donnée</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </b-card-body>
          </b-card>
        </b-col>
      `
    }
  }
}
</script>
