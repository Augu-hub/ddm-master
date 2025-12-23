<template>
  <VerticalLayout>
    <Head title="Critères de criticité — Référentiels" />

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="fw-semibold text-primary mb-0">
        <i class="ti ti-activity me-2"></i> Critères & niveaux de criticité
      </h4>
    </div>

    <b-row>
      <b-col md="6">
        <b-card class="shadow-sm mb-3">
          <h6 class="fw-semibold mb-2 text-primary">Critères</h6>
          <b-table-simple bordered hover small>
            <b-thead class="bg-light">
              <b-tr><b-th>Code</b-th><b-th>Libellé</b-th><b-th class="text-center">Poids</b-th></b-tr>
            </b-thead>
            <b-tbody>
              <b-tr v-for="(c, i) in form.criteria" :key="i">
                <b-td><input v-model="c.code" class="form-control form-control-sm" @change="autoSave()" /></b-td>
                <b-td><input v-model="c.label" class="form-control form-control-sm" @change="autoSave()" /></b-td>
                <b-td class="text-center"><input type="number" v-model.number="c.weight" class="form-control form-control-sm text-center" @change="autoSave()" /></b-td>
              </b-tr>
              <b-tr>
                <b-td colspan="3" class="text-center">
                  <b-button size="sm" variant="outline-primary" @click="addCriterion"><i class="ti ti-plus me-1"></i> Ajouter</b-button>
                </b-td>
              </b-tr>
            </b-tbody>
          </b-table-simple>
        </b-card>
      </b-col>

      <b-col md="6">
        <b-card class="shadow-sm mb-3">
          <h6 class="fw-semibold mb-2 text-primary">Niveaux</h6>
          <b-table-simple bordered hover small>
            <b-thead class="bg-light">
              <b-tr><b-th>Code</b-th><b-th>Libellé</b-th><b-th class="text-center">Poids</b-th><b-th>Couleur</b-th></b-tr>
            </b-thead>
            <b-tbody>
              <b-tr v-for="(l, i) in form.levels" :key="i">
                <b-td><input v-model="l.code" class="form-control form-control-sm" @change="autoSave()" /></b-td>
                <b-td><input v-model="l.label" class="form-control form-control-sm" @change="autoSave()" /></b-td>
                <b-td class="text-center"><input type="number" v-model.number="l.weight" class="form-control form-control-sm text-center" @change="autoSave()" /></b-td>
                <b-td><input type="color" v-model="l.color" class="form-control form-control-color form-control-sm w-auto" @input="autoSave()" /></b-td>
              </b-tr>
              <b-tr>
                <b-td colspan="4" class="text-center">
                  <b-button size="sm" variant="outline-primary" @click="addLevel"><i class="ti ti-plus me-1"></i> Ajouter</b-button>
                </b-td>
              </b-tr>
            </b-tbody>
          </b-table-simple>
        </b-card>
      </b-col>
    </b-row>

    <div class="fixed-bottom bg-white border-top py-2 shadow-sm text-end pe-4">
      <b-button size="lg" variant="success" :disabled="isSaving" @click="save">
        <i class="ti ti-device-floppy me-1"></i>{{ isSaving ? 'Enregistrement...' : 'Enregistrer les modifications' }}
      </b-button>
    </div>
  </VerticalLayout>
</template>

<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import { ref } from 'vue'

const props = defineProps<{ criteria: Array<any>, levels: Array<any> }>()
const form = useForm({
  criteria: JSON.parse(JSON.stringify(props.criteria || [])),
  levels: JSON.parse(JSON.stringify(props.levels || [])),
})
const isSaving = ref(false)

const addCriterion = () => form.criteria.push({ code: '', label: '', weight: 1 })
const addLevel = () => form.levels.push({ code: '', label: '', weight: 1, color: '#cccccc' })

let timer: any = null
const autoSave = () => { clearTimeout(timer); timer = setTimeout(() => save(), 1200) }

const save = () => {
  isSaving.value = true
  form.post(route('process.core.process.settings.criticality.save'), {
    preserveScroll: true,
    onFinish: () => (isSaving.value = false),
  })
}
</script>
