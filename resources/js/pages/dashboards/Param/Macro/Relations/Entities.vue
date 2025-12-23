<template>
  <VerticalLayout>
    <Head title="Relations Processus / Entités" />

    <!-- HEADER -->
    <b-card class="shadow-sm mb-3 border-0 px-3 py-2">
      <h4 class="fw-bold mb-0">
        <i class="ti ti-building text-primary me-1"></i>
        Sélection d’une Entité
      </h4>
      <small class="text-muted">
        Choisissez une entité pour configurer ses relations entre processus.
      </small>
    </b-card>

    <b-card class="shadow-sm border-0">
      <b-row class="g-2">

        <b-col
          v-for="e in entities"
          :key="e.id"
          cols="12"
          md="6"
          lg="4"
        >
          <b-card class="shadow-xs entity-card" @click="openEntity(e)">
            <div class="d-flex align-items-center gap-2">
              <i class="ti ti-building-skyscraper text-primary fs-3"></i>
              <div>
                <h6 class="fw-bold mb-0">{{ e.name }}</h6>
                <small class="text-muted">{{ e.email || '—' }}</small>
              </div>
            </div>
          </b-card>
        </b-col>

      </b-row>
    </b-card>

  </VerticalLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import VerticalLayout from "@/layoutsparam/VerticalLayout.vue"

const props = defineProps({
  entities: Array
})

const openEntity = (e) => {
  window.location.href = `/m/param.projects/macro/relations/${e.id}`
}
</script>

<style scoped>
.entity-card {
  padding: 12px;
  border-radius: 10px;
  cursor: pointer;
  transition: 0.2s ease-in-out;
  background: #ffffff;
  border: 1px solid #eef1f4 !important;
}
.entity-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 3px 8px rgba(0,0,0,.08);
}
</style>
