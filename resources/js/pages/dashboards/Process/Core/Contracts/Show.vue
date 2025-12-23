<template>
  <VerticalLayout>
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
      <h6 class="fw-semibold mb-3">
        <i class="ti ti-table"></i> Données du contrat ({{ contract.rows.length }} ligne(s))
      </h6>

      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle doc-table">
          <thead class="table-light text-center">
            <tr>
              <th style="width: 30%">Données de sortie</th>
              <th style="width: 15%">Utilisateur</th>
              <th style="width: 25%">Attentes utilisateurs</th>
              <th style="width: 20%">Acteur (Qui sait faire ?)</th>
              <th style="width: 10%">Documents</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="(row, i) in contract.rows" :key="i">
              <td class="fw-semibold">{{ row.output }}</td>
              <td class="small">{{ row.user || '—' }}</td>
              <td class="small">{{ row.expectation || '—' }}</td>
              <td class="small">{{ row.actor_user || '—' }}</td>

              <td class="text-center small">
                <span v-if="row.document" class="text-success">
                  <i class="ti ti-file-text me-1"></i>{{ row.file_name }}
                </span>
                <span v-else class="text-muted">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </b-card>

    <!-- ACTIONS -->
    <div class="d-flex gap-2 justify-content-between">
      <b-button variant="outline-secondary" @click="goBack">
        <i class="ti ti-arrow-left"></i> Retour
      </b-button>

      <b-button
        variant="outline-danger"
        @click="deleteContract"
      >
        <i class="ti ti-trash"></i> Supprimer
      </b-button>
    </div>
  </VerticalLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import VerticalLayout from '@/layouts/VerticalLayout.vue'

const props = defineProps({
  contract: {
    type: Object,
    required: true
  }
})

function goBack() {
  router.visit(route('process.contract.index'))
}

function deleteContract() {
  if (confirm('Êtes-vous sûr de vouloir supprimer ce contrat?')) {
    const contractId = props.contract.id
    router.delete(route('process.contract.destroy', contractId), {
      onSuccess: () => {
        router.visit(route('process.contract.index'))
      }
    })
  }
}
</script>

<style scoped>
.doc-table td,
.doc-table th {
  padding: 0.5rem !important;
  font-size: 0.85rem !important;
}

.doc-table {
  margin-bottom: 0;
}
</style>