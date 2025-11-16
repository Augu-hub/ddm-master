<template>
  <VerticalLayout>

    <!-- HEADER SÉCURISÉ -->
    <Head :title="'Contrat — ' + contract.title" />

    <!-- BLOC DOCUMENT - EN-TÊTE -->
    <b-card class="shadow-sm mb-3 p-3">

      <div class="d-flex justify-content-between">
        <div>
          <h5 class="fw-bold m-0">Contrat d'interfaces</h5>
          <small class="text-muted">CPEC — DIADDEM</small>
        </div>

        <div class="text-end small">
          <div><strong>Date création :</strong> {{ contract.created_at }}</div>
          <div><strong>Dernière mise à jour :</strong> {{ contract.updated_at }}</div>
        </div>
      </div>

      <hr />

      <b-row class="g-2 small">
        <b-col cols="6">
          <strong>Processus :</strong>
          <span class="ms-1">{{ contract.process }}</span>
        </b-col>

        <b-col cols="6">
          <strong>Propriétaire :</strong>
          <span class="ms-1">{{ contract.owner }}</span>
        </b-col>

        <b-col cols="12">
          <strong>Finalité :</strong>
          <div class="ms-1">{{ contract.purpose }}</div>
        </b-col>
      </b-row>

    </b-card>

    <!-- TABLEAU PRINCIPAL -->
    <b-card class="shadow-sm p-3 mb-3">

      <h6 class="fw-semibold mb-2">Tableau des interfaces</h6>

      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle doc-table">
          <thead class="table-light text-center">
            <tr>
              <th>Données de sortie</th>
              <th>Utilisateur</th>
              <th>Attentes utilisateurs</th>
              <th>Acteur (Qui sait faire ?)</th>
              <th>Documents attachés</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="(row, i) in contract.rows" :key="i">

              <td>{{ row.output }}</td>
              <td>{{ row.user }}</td>
              <td>{{ row.expectation }}</td>
              <td>{{ row.actor }}</td>

              <td>
                <span v-if="row.document">
                  <i class="ti ti-file-text me-1"></i>{{ row.document }}
                </span>
                <span v-else class="text-muted">—</span>
              </td>

            </tr>
          </tbody>

        </table>
      </div>

    </b-card>

    <!-- INDICATEURS -->
    <b-card class="shadow-sm p-3">
      <h6 class="fw-semibold mb-2">Indicateurs</h6>

      <b-row class="g-2 small">

        <b-col cols="6">
          <strong>Indicateurs d'activité :</strong>
          <ul class="mt-1">
            <li v-for="(ind,i) in contract.activity_indicators" :key="i">
              {{ ind }}
            </li>
          </ul>
        </b-col>

        <b-col cols="6">
          <strong>Indicateurs de performance :</strong>
          <ul class="mt-1">
            <li v-for="(ind,i) in contract.performance_indicators" :key="i">
              {{ ind }}
            </li>
          </ul>
        </b-col>

      </b-row>
    </b-card>

  </VerticalLayout>
</template>

<script setup>
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import { Head } from '@inertiajs/vue3'

/* ===============================
   DONNÉES MANUELLES (mock data)
   Tu remplaceras par "props.contract"
   =============================== */

const contract = {
  title: "Contrat d’interfaces — Crédit",
  created_at: "10/05/2017",
  updated_at: "Création",
  
  process: "CRÉDIT",
  owner: "Directeur d'Exploitation",
  purpose: "Octroyer des crédits saints et utiles pour les membres",

  rows: [
    {
      output: "Client enregistré et devenu membre",
      user: "—",  
      expectation: "—",
      actor: "Chargé des prêts",
      document: null
    },
    {
      output: "Dossier constitué et validé",
      user: "—",
      expectation: "—",
      actor: "Chargé des prêts",
      document: "dossier.pdf"
    },
    {
      output: "Garanties constituées et dossier validé par comité de crédit",
      user: "—",
      expectation: "—",
      actor: "Chef Agence",
      document: null
    },
    {
      output: "Chèque acquitté, prêt octroyé",
      user: "—",
      expectation: "—",
      actor: "Chef Service Financier",
      document: "preuve.png"
    },
    {
      output: "Échéancier, suivi, tableau des impayés",
      user: "—",
      expectation: "—",
      actor: "Chargé des prêts",
      document: null
    },
    {
      output: "Crédits recouvrés, actions judiciaires, dépréciations",
      user: "—",
      expectation: "—",
      actor: "Chef Service Crédit",
      document: null
    },
    {
      output: "Crédits octroyés, Crédits remboursés, clientèle ciblée",
      user: "—",
      expectation: "—",
      actor: "Directeur d'exploitation",
      document: null
    }
  ],

  activity_indicators: [
    "1. Délai moyen de traitement d'une demande (5 jours)"
  ],

  performance_indicators: [
    "2. Note de satisfaction client sur la fonction commerciale : 5/10"
  ]
}
</script>

<style scoped>
.doc-table td,
.doc-table th {
  padding: .38rem !important;
  font-size: .78rem !important;
}
</style>
