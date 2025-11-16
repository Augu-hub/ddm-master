<template>
  <VerticalLayout>
    <Head title="Référentiels Processus" />

    <b-row>
      <b-col cols="12" class="mb-3">
        <h4 class="m-0 fw-semibold">Référentiels Processus</h4>
        <p class="text-muted mb-0">Accédez aux différents référentiels utilisés par le module Processus</p>
      </b-col>
    </b-row>

    <b-row class="g-3">
      <b-col md="6" xl="4" v-for="card in cards" :key="card.key">
        <b-card class="h-100">
          <h5 class="fw-semibold mb-1"><i :class="card.icon" class="me-1"></i>{{ card.title }}</h5>
          <p class="text-muted mb-3">{{ card.desc }}</p>
          <Link :href="card.href" class="btn btn-primary btn-sm">
            Ouvrir
          </Link>
        </b-card>
      </b-col>
    </b-row>
  </VerticalLayout>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps<{
  routes: Record<string, string>
}>()

const cards = [
  { key:'maturity', title:'Niveaux de maturité', icon:'ti ti-chart-bar', desc:'Échelles, rangs et couleurs', href: props.routes.maturity },
  { key:'criticality', title:'Criticité (critères & niveaux)', icon:'ti ti-flame', desc:'Paramètres de criticité', href: props.routes.criticality },
  { key:'raci', title:'Rôles RACI', icon:'ti ti-users-group', desc:'Responsible / Accountable / Consulted / Informed', href: props.routes.raci },
  { key:'idea', title:'Axes IDEA', icon:'ti ti-bulb', desc:'Impact, Difficulté, Efficience, Acceptation', href: props.routes.idea },
  { key:'kpi', title:'Catégories KPI', icon:'ti ti-graph', desc:'Qualité, Délai, Coût, Satisfaction, Productivité', href: props.routes.kpi },
  { key:'links', title:'Types de liens (processus)', icon:'ti ti-link', desc:'Amont, Aval, Support, Contrôle', href: props.routes.links },
  { key:'controls', title:'Types de contrôles (activité)', icon:'ti ti-shield-check', desc:'Préventif, Détectif, Correctif', href: props.routes.controls },
]
</script>