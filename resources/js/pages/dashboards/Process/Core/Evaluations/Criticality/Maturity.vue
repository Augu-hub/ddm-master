<template>
  <VerticalLayout>
    <Head title="Maturité — Référentiel" />

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h5 class="fw-semibold text-primary mb-0">
          <i class="ti ti-stairs-up me-2"></i> Critères et niveaux
        </h5>
      </div>
      <b-button size="sm" variant="primary" @click="addScale">
        <i class="ti ti-plus me-1"></i> Ajouter critère
      </b-button>
    </div>

    <div v-if="!form.items.length" class="alert alert-info text-center">
      <i class="ti ti-info-circle me-2"></i> Aucun critère.
    </div>

    <b-card v-for="(scale, i) in form.items" :key="scale.id || i" class="mb-4 shadow-sm border-0">
      <!-- SECTION CRITÈRE -->
      <div class="p-3 bg-light mb-3 rounded">
        <div class="d-flex justify-content-between align-items-center">
          <div class="flex-grow-1">
            <div class="d-flex gap-2">
              <input
                v-model="scale.code"
                class="form-control form-control-sm fw-semibold"
                style="max-width: 120px"
                placeholder="Code"
                :disabled="!!scale.id"
              />
              <input
                v-model="scale.label"
                class="form-control form-control-sm fw-semibold"
                placeholder="Libellé du critère"
                :disabled="!!scale.id"
              />
            </div>
            <textarea
              v-model="scale.description"
              rows="1"
              class="form-control form-control-sm mt-2 text-muted"
              placeholder="Description..."
              :disabled="!!scale.id"
            ></textarea>
          </div>
          <div class="d-flex gap-1 ms-2">
            <b-button 
              v-if="!scale.id"
              size="sm" 
              variant="success" 
              @click="saveScale(scale)" 
              class="px-2"
              :disabled="saving"
            >
              <i class="ti ti-device-floppy"></i> Créer
            </b-button>
            <b-button 
              size="sm" 
              variant="danger" 
              @click="removeScale(scale)" 
              class="px-2"
              :disabled="saving"
            >
              <i class="ti ti-trash"></i>
            </b-button>
          </div>
        </div>
      </div>

      <!-- SECTION NIVEAUX (affichée seulement si scale.id existe) -->
      <div v-if="scale.id" class="table-responsive">
        <table class="table table-bordered table-hover table-sm align-middle mb-0">
          <thead class="table-light">
            <tr class="text-center fw-semibold">
              <th style="width: 10%">Score</th>
              <th style="width: 25%">Libellé</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(lvl, j) in (scale.levels || [])" :key="lvl.id || j">
              <td class="text-center fw-bold text-primary">{{ lvl.level_score }}</td>
              <td>
                <input
                  v-model="lvl.level_label"
                  class="form-control form-control-sm"
                  placeholder="Libellé"
                />
              </td>
              <td>
                <textarea
                  v-model="lvl.level_description"
                  rows="1"
                  class="form-control form-control-sm"
                  placeholder="Description..."
                ></textarea>
              </td>
            </tr>

            <tr v-if="!(scale.levels && scale.levels.length)">
              <td colspan="3" class="text-center text-muted py-2">Aucun niveau.</td>
            </tr>
          </tbody>
        </table>

        <div class="text-end mt-2">
          <b-button
            size="sm"
            variant="outline-success"
            @click="saveLevels(scale)"
            :disabled="saving"
          >
            <i class="ti ti-device-floppy me-1"></i> Sauvegarder niveaux
          </b-button>
        </div>
      </div>

      <!-- MESSAGE D'ATTENTE -->
      <div v-if="!scale.id" class="alert alert-warning mb-0">
        <i class="ti ti-alert-circle me-2"></i> Créez d'abord le critère pour ajouter les niveaux
      </div>
    </b-card>
  </VerticalLayout>
</template>

<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3"
import VerticalLayout from "@/layoutsparam/VerticalLayout.vue"
import { ref } from "vue"

interface Level {
  id: number | null
  level_score: number
  level_label: string
  level_description: string
}

interface Scale {
  id: number | null
  code: string
  label: string
  description: string
  levels?: Level[]
}

const props = defineProps<{ items: Scale[] }>()
const saving = ref(false)

const form = useForm({
  items: (props.items || []).map((item: Scale) => ({
    ...item,
    levels: item.levels || [],
  })),
})

const addScale = () => {
  form.items.push({
    id: null,
    code: "",
    label: "",
    description: "",
    levels: [],
  })
}

const saveScale = (scale: Scale) => {
  if (!scale.code || !scale.label) {
    alert("Remplissez le code et le libellé")
    return
  }
  
  saving.value = true
  form.transform(() => ({
    id: scale.id,
    code: scale.code,
    label: scale.label,
    description: scale.description,
  }))
  form.post(route("process.core.process.evaluations.criticality.maturity.save"), {
    preserveScroll: true,
    onSuccess: () => {
      window.location.reload()
    },
    onFinish: () => {
      saving.value = false
    },
  })
}

const saveLevels = (scale: Scale) => {
  if (!scale.id) {
    alert("Créez le critère d'abord")
    return
  }
  
  saving.value = true
  form.transform(() => ({
    levels: scale.levels || [],
  }))
  form.post(route("process.core.process.evaluations.criticality.maturity.save-levels", scale.id), {
    preserveScroll: true,
    onSuccess: () => {
      alert("✅ Niveaux enregistrés.")
    },
    onFinish: () => {
      saving.value = false
    },
  })
}

const removeScale = (scale: Scale) => {
  if (!scale.id) {
    form.items = form.items.filter((i) => i !== scale)
    return
  }
  if (confirm("Supprimer ce critère et ses niveaux ?")) {
    saving.value = true
    form.delete(route("process.core.process.evaluations.criticality.maturity.delete", scale.id), {
      preserveScroll: true,
      onSuccess: () => {
        window.location.reload()
      },
      onFinish: () => {
        saving.value = false
      },
    })
  }
}
</script>