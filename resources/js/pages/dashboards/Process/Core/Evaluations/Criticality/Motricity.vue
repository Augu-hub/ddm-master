<template>
  <VerticalLayout>
    <Head title="Motricité — Référentiel" />
    <b-card class="shadow-sm border-0">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-semibold text-primary mb-0">
          <i class="ti ti-engine me-2"></i> Échelle de motricité
        </h5>
        <Link :href="route('process.core.process.evaluations.criticality.index')" class="btn btn-light btn-sm">
          <i class="ti ti-arrow-left"></i> Retour
        </Link>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" style="font-size: 0.8rem">
          <thead class="table-primary">
            <tr>
              <th style="width:10%">Code</th>
              <th style="width:30%">Libellé</th>
              <th>Description</th>
              <th class="text-center" style="width:12%">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, i) in form.items" :key="i">
              <td><input v-model="item.code" class="form-control form-control-sm text-center fw-semibold" /></td>
              <td><input v-model="item.label" class="form-control form-control-sm" /></td>
              <td><textarea v-model="item.description" rows="1" class="form-control form-control-sm"></textarea></td>
              <td class="text-center">
                <b-button size="sm" variant="success" @click="save(item)">
                  <i class="ti ti-device-floppy"></i>
                </b-button>
              </td>
            </tr>

            <tr>
              <td colspan="4" class="text-center">
                <b-button size="sm" variant="outline-primary" @click="addRow">
                  <i class="ti ti-plus me-1"></i> Ajouter un niveau
                </b-button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-3">
        <b-button size="sm" variant="success" @click="saveAll">✅ ENREGISTRER</b-button>
        <Link :href="route('process.core.process.evaluations.criticality.index')" class="btn btn-secondary btn-sm">✕ FERMER</Link>
      </div>
    </b-card>
  </VerticalLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps<{ items: Array<any> }>()
const form = useForm({ items: JSON.parse(JSON.stringify(props.items || [])) })

const addRow = () => form.items.push({ code: '', label: '', description: '' })

const save = (item) => {
  form.transform(() => item)
  form.post(route('process.core.process.evaluations.criticality.update', 'process_motricity_scales'), { preserveScroll: true })
}

const saveAll = () => {
  form.post(route('process.core.process.evaluations.criticality.store', 'process_motricity_scales'), { preserveScroll: true })
}
</script>
