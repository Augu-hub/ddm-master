<template>
  <VerticalLayout>
    <Head title="Normes de criticité — Référentiels" />

    <!-- EN-TÊTE -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="fw-semibold text-primary mb-0">
        <i class="ti ti-color-swatch me-2"></i> Normes de criticité
      </h4>
      <small class="text-muted">
        Dernière borne doit atteindre 
        <span class="fw-bold" :class="endPercent === 100 ? 'text-success' : 'text-danger'">
          {{ endPercent }} %
        </span>
      </small>
    </div>

    <!-- TABLE -->
    <b-card class="shadow-sm border-0">
      <div class="table-responsive">
        <b-table-simple hover bordered small class="align-middle">
          <b-thead class="bg-light">
            <b-tr>
              <b-th class="text-center">Min (%)</b-th>
              <b-th class="text-center">Max (%)</b-th>
              <b-th>Couleur</b-th>
              <b-th>Libellé</b-th>
              <b-th>Niveau</b-th>
              <b-th>Actions collectives</b-th>
              <b-th>Modifié par</b-th>
              <b-th>Dernière mise à jour</b-th>
            </b-tr>
          </b-thead>

          <b-tbody>
            <b-tr v-for="(n, i) in form.norms" :key="i">
              <!-- Min / Max -->
              <b-td class="text-center">
                <input type="number" class="form-control form-control-sm text-center"
                       v-model.number="n.min_percent" min="0" max="100" step="1" @change="autoSave()" />
              </b-td>
              <b-td class="text-center">
                <input type="number" class="form-control form-control-sm text-center"
                       v-model.number="n.max_percent" min="0" max="100" step="1" @change="autoSave()" />
              </b-td>

              <!-- Couleur -->
              <b-td>
                <div class="d-flex align-items-center gap-2">
                  <input type="color" v-model="n.color"
                         class="form-control form-control-color form-control-sm w-auto" @input="autoSave()" />
                  <span class="badge border" :style="{ backgroundColor: n.color }">{{ n.color }}</span>
                </div>
              </b-td>

              <!-- Libellés et actions -->
              <b-td><input type="text" class="form-control form-control-sm" v-model="n.alert_label" @change="autoSave()" /></b-td>
              <b-td><input type="text" class="form-control form-control-sm" v-model="n.alert_level" @change="autoSave()" /></b-td>
              <b-td><textarea class="form-control form-control-sm" v-model="n.actions" rows="1" @change="autoSave()"></textarea></b-td>

              <b-td class="text-muted text-center">#{{ n.user_id || '—' }}</b-td>
              <b-td class="text-muted small">{{ n.updated_at ? new Date(n.updated_at).toLocaleString() : '—' }}</b-td>
            </b-tr>

            <b-tr v-if="!form.norms.length">
              <b-td colspan="8" class="text-center text-muted py-3">
                Aucune norme enregistrée
              </b-td>
            </b-tr>
          </b-tbody>
        </b-table-simple>
      </div>
    </b-card>

    <!-- BOUTON ENREGISTREMENT FIXE -->
    <div class="fixed-bottom bg-white border-top py-2 shadow-sm text-end pe-4">
      <b-button size="lg" variant="success" :disabled="isSaving || endPercent !== 100" @click="save">
        <i class="ti ti-device-floppy me-1"></i>
        {{ isSaving ? 'Enregistrement...' : 'Enregistrer les modifications' }}
      </b-button>
    </div>
  </VerticalLayout>
</template>

<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import { computed, ref } from 'vue'

// ✅ Props initiales
const props = defineProps<{ items: Array<any> }>()

// ✅ Formulaire Inertia
const form = useForm({
  norms: JSON.parse(JSON.stringify(props.items || []))
})

const isSaving = ref(false)

// ✅ Dernière borne (doit finir à 100 %)
const endPercent = computed(() => {
  if (!form.norms.length) return 0
  return form.norms[form.norms.length - 1].max_percent || 0
})

// ✅ Sauvegarde automatique
let saveTimeout: any = null
const autoSave = () => {
  clearTimeout(saveTimeout)
  saveTimeout = setTimeout(() => save(), 1200)
}

// ✅ Sauvegarde
const save = () => {
  if (endPercent.value !== 100) {
    alert('⚠️ La dernière borne maximale doit être égale à 100 %.')
    return
  }

  isSaving.value = true
  form.post(route('process.core.process.settings.criticality-norms.save'), {
    preserveScroll: true,
    onSuccess: () => { isSaving.value = false },
    onError: () => {
      isSaving.value = false
      alert('❌ Erreur lors de la sauvegarde.')
    }
  })
}
</script>

<style scoped>
.fixed-bottom { z-index: 1030; }
</style>
