<template>
  <VerticalLayout>
    <Head title="Criticité — Référentiels des 4 axes" />

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="fw-semibold text-primary mb-0">
        <i class="ti ti-graph me-2"></i> Échelles de criticité (4 axes)
      </h4>
      <small class="text-muted">Maturité • Motricité • Transversalité • Stratégique</small>
    </div>

    <b-row class="g-3">
      <b-col md="6" lg="3" v-for="axis in axes" :key="axis.code">
        <b-card class="shadow-sm border-0 h-100">
          <div class="d-flex align-items-center mb-2">
            <i :class="axis.icon + ' text-primary me-2 fs-5'"></i>
            <h6 class="fw-semibold mb-0">{{ axis.label }}</h6>
          </div>
          <p class="text-muted small mb-3">{{ axis.desc }}</p>
          <Link :href="route(axis.route)" class="btn btn-outline-primary btn-sm w-100">
            <i class="ti ti-edit me-1"></i> Gérer les niveaux
          </Link>
        </b-card>
      </b-col>
    </b-row>
  </VerticalLayout>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const axes = [
  {
    code: 'maturity',
    label: 'Maturité',
    desc: 'Mesure du niveau de formalisation et de maîtrise du processus.',
    icon: 'ti ti-stairs-up',
    route: 'process.core.process.evaluations.criticality.maturity',
  },
  {
    code: 'motricity',
    label: 'Motricité',
    desc: 'Évalue la contribution du processus au fonctionnement global.',
    icon: 'ti ti-engine',
    route: 'process.core.process.evaluations.criticality.motricity',
  },
  {
    code: 'transversality',
    label: 'Transversalité',
    desc: 'Apprécie les interactions et interdépendances avec d’autres processus.',
    icon: 'ti ti-arrows-diagonal',
    route: 'process.core.process.evaluations.criticality.transversality',
  },
  {
    code: 'strategic',
    label: 'Poids stratégique',
    desc: 'Mesure la contribution du processus aux objectifs stratégiques.',
    icon: 'ti ti-target-arrow',
    route: 'process.core.process.evaluations.criticality.strategic',
  },
]
</script>
