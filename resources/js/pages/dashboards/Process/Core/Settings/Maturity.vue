<template>
  <VerticalLayout>
    <Head title="Échelles de maturité — Référentiels" />

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="fw-semibold text-primary mb-0">
        <i class="ti ti-stairs-up me-2"></i> Échelles de maturité
      </h4>
    </div>

    <b-card class="shadow-sm border-0">
      <div class="table-responsive">
        <b-table-simple hover bordered small>
          <b-thead class="bg-light">
            <b-tr>
              <b-th>Code</b-th>
              <b-th>Libellé</b-th>
              <b-th class="text-center">Score min</b-th>
              <b-th class="text-center">Score max</b-th>
              <b-th class="text-center">Rang</b-th>
              <b-th class="text-center">Couleur</b-th>
            </b-tr>
          </b-thead>
          <b-tbody>
            <b-tr v-for="(i, idx) in form.items" :key="idx">
              <b-td><input v-model="i.code" class="form-control form-control-sm" @change="autoSave()" /></b-td>
              <b-td><input v-model="i.label" class="form-control form-control-sm" @change="autoSave()" /></b-td>
              <b-td class="text-center"><input type="number" v-model.number="i.min_score" class="form-control form-control-sm text-center" @change="autoSave()" /></b-td>
              <b-td class="text-center"><input type="number" v-model.number="i.max_score" class="form-control form-control-sm text-center" @change="autoSave()" /></b-td>
              <b-td class="text-center"><input type="number" v-model.number="i.rank" class="form-control form-control-sm text-center" @change="autoSave()" /></b-td>
              <b-td>
                <input type="color" v-model="i.color" class="form-control form-control-color form-control-sm w-auto" @input="autoSave()" />
              </b-td>
            </b-tr>

            <b-tr>
              <b-td colspan="6" class="text-center">
                <b-button size="sm" variant="outline-primary" @click="addRow">
                  <i class="ti ti-plus me-1"></i> Ajouter une échelle
                </b-button>
              </b-td>
            </b-tr>
          </b-tbody>
        </b-table-simple>
      </div>
    </b-card>

    <div class="fixed-bottom bg-white border-top py-2 shadow-sm text-end pe-4">
      <b-button size="lg" variant="success" :disabled="isSaving" @click="save">
        <i class="ti ti-device-floppy me-1"></i>{{ isSaving ? 'Enregistrement...' : 'Enregistrer' }}
      </b-button>
    </div>
  </VerticalLayout>
</template>

<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import { ref } from 'vue'

const props = defineProps<{ items: Array<any> }>()
const form = useForm({ items: JSON.parse(JSON.stringify(props.items || [])) })
const isSaving = ref(false)

const addRow = () => {
  form.items.push({ code: '', label: '', min_score: 0, max_score: 0, rank: 0, color: '#cccccc' })
}

let timer: any = null
const autoSave = () => { clearTimeout(timer); timer = setTimeout(() => save(), 1200) }

const save = () => {
  isSaving.value = true
  form.post(route('process.core.process.settings.maturity-scales.save'), {
    preserveScroll: true,
    onFinish: () => (isSaving.value = false),
  })
}
</script>

<style scoped>
.fixed-bottom { z-index: 1030; }
</style>
