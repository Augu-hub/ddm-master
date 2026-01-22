<template>
  <VerticalLayout>
    <Head title="Créer Mission — Audit Interne" />

    <!-- HEADER STICKY -->
    <b-row class="mb-3">
      <b-col>
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <i class="ti ti-file-plus text-primary fs-5"></i>
            <h4 class="m-0 fw-semibold">Créer une Mission</h4>
            <small class="text-muted ms-2">Nouvelle mission d'audit</small>
          </div>
          <div class="d-flex gap-2">
            <b-button variant="outline-secondary" size="sm" @click="$inertia.visit(route('missions.index'))">
              ← Retour
            </b-button>
          </div>
        </div>
      </b-col>
    </b-row>

    <b-row class="g-3">
      <!-- Colonne gauche : Formulaire -->
      <b-col lg="8">
        <b-card no-body class="shadow-sm h-100">
          <b-card-header class="bg-primary text-white py-2 px-3">
            <h6 class="mb-0">Informations de la mission</h6>
          </b-card-header>

          <b-card-body class="p-3">
            <b-form @submit.prevent="submit">
              <b-row class="g-2">

                <!-- Exercice -->
                <b-col md="6">
                  <label class="form-label fw-bold text-primary mb-1">Exercice d'audit *</label>
                  <b-form-select v-model="form.audit_exercise_id" :options="exerciseOptions" required />
                </b-col>

                <!-- Type de mission -->
                <b-col md="6">
                  <label class="form-label fw-bold text-primary mb-1">Type de mission *</label>
                  <b-form-select v-model="form.mission_type_id" :options="typeOptions" required />
                </b-col>

                <!-- Numéro FPM + Code (généré) -->
                <b-col md="6">
                  <label class="form-label fw-bold mb-1">Numéro FPM</label>
                  <b-form-input v-model.trim="form.fpm_number" placeholder="FPM-XXXX-XXXX" class="form-control-sm" />
                </b-col>
                <b-col md="6">
                  <label class="form-label fw-bold mb-1">Code Mission (généré)</label>
                  <b-form-input :value="generatedCode" disabled class="form-control-sm bg-light" />
                </b-col>

                <!-- Intitulé -->
                <b-col cols="12">
                  <label class="form-label fw-bold text-primary mb-1">Intitulé de la mission *</label>
                  <b-form-input v-model.trim="form.title" required class="form-control-sm" />
                </b-col>

                <!-- Objectif -->
                <b-col cols="12">
                  <label class="form-label fw-bold mb-1">Objectif principal</label>
                  <b-form-textarea v-model.trim="form.objective" rows="2" class="form-control-sm" />
                </b-col>

                <!-- Domaine + Référence -->
                <b-col md="6">
                  <label class="form-label fw-bold mb-1">Domaine</label>
                  <b-form-input v-model.trim="form.domain" class="form-control-sm" />
                </b-col>
                <b-col md="6">
                  <label class="form-label fw-bold mb-1">Référence document</label>
                  <b-form-input v-model.trim="form.reference_document" class="form-control-sm" />
                </b-col>

                <!-- Priorité -->
                <b-col md="4">
                  <label class="form-label fw-bold text-primary mb-1">Priorité *</label>
                  <b-form-select v-model="form.priority" :options="priorityOptions" required class="form-select-sm" />
                </b-col>

                <!-- Dates -->
                <b-col md="4">
                  <label class="form-label fw-bold mb-1">Début prévu</label>
                  <b-form-input v-model="form.planned_start_date" type="date" class="form-control-sm" />
                </b-col>
                <b-col md="4">
                  <label class="form-label fw-bold mb-1">Fin prévue</label>
                  <b-form-input v-model="form.planned_end_date" type="date" class="form-control-sm" />
                </b-col>

                <!-- Boutons bas gauche -->
                <b-col cols="12" class="text-end pt-2">
                  <b-button variant="light" size="sm" @click="resetForm" class="me-2">
                    <i class="ti ti-plus me-1"></i> Nouveau
                  </b-button>
                  <b-button variant="warning" size="sm" type="submit" :disabled="form.processing || loading">
                    <i class="ti ti-device-floppy me-1"></i>
                    {{ form.processing ? 'Enregistrement…' : 'Valider' }}
                  </b-button>
                </b-col>
              </b-row>
            </b-form>
          </b-card-body>
        </b-card>
      </b-col>

      <!-- Colonne droite : Risques & Compétences -->
      <b-col lg="4">
        <!-- RISQUES -->
        <b-card no-body class="shadow-sm mb-3">
          <b-card-header class="bg-danger text-white py-2 px-3">
            <h6 class="mb-0">Risques couverts</h6>
          </b-card-header>
          <b-card-body class="p-2" style="max-height:280px; overflow-y:auto;">
            <div v-for="risk in risks" :key="risk.id" class="form-check form-check-sm">
              <input class="form-check-input" type="checkbox" :id="'risk-'+risk.id" :value="risk.id" v-model="form.risk_ids" />
              <label class="form-check-label small" :for="'risk-'+risk.id">
                <strong>{{ risk.code }}</strong> — {{ risk.label }}
              </label>
            </div>
          </b-card-body>
        </b-card>

        <!-- COMPÉTENCES -->
        <b-card no-body class="shadow-sm">
          <b-card-header class="bg-success text-white py-2 px-3">
            <h6 class="mb-0">Compétences nécessaires</h6>
          </b-card-header>
          <b-card-body class="p-2" style="max-height:280px; overflow-y:auto;">
            <div v-for="comp in competencies" :key="comp.id" class="d-flex align-items-center justify-content-between mb-1">
              <div class="form-check form-check-sm flex-grow-1">
                <input class="form-check-input" type="checkbox" :id="'comp-'+comp.id" :value="comp.id" v-model="form.competency_ids" />
                <label class="form-check-label small" :for="'comp-'+comp.id">
                  {{ comp.code }} — {{ comp.name }}
                </label>
              </div>
              <b-form-select v-if="form.competency_ids.includes(comp.id)" v-model="form.competency_levels[comp.id]" size="sm" style="width:80px;">
                <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
              </b-form-select>
            </div>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- MISSIONS PLANIFIÉES (comme dans ton screenshot) -->
    <b-card no-body class="shadow-sm mt-4">
      <b-card-header class="bg-secondary text-white py-2 px-3 d-flex justify-content-between">
        <h6 class="mb-0">Missions planifiées (indicatif)</h6>
        <small>{{ plannedMissions.length }} missions</small>
      </b-card-header>
      <b-card-body class="p-0">
        <DataTable :value="plannedMissions" size="small" class="pv-table flat" :paginator="true" :rows="5">
          <Column field="code" header="Code" style="width:140px">
            <template #body="{ data }">
              <strong class="text-primary">{{ data.code }}</strong>
            </template>
          </Column>
          <Column field="title" header="Libellé" />
          <Column field="fpm_number" header="N° FPM" style="width:140px" />
          <Column field="objective" header="Objectif" :showFilterMatchModes="false">
            <template #body="{ data }">
              <small class="text-muted">{{ truncate(data.objective, 40) }}</small>
            </template>
          </Column>
          <Column field="planned_start_date" header="Début" style="width:110px">
            <template #body="{ data }">{{ formatDate(data.planned_start_date) }}</template>
          </Column>
          <Column field="planned_end_date" header="Fin" style="width:110px">
            <template #body="{ data }">{{ formatDate(data.planned_end_date) }}</template>
          </Column>
          <Column header="Source" style="width:140px" />
        </DataTable>
      </b-card-body>
    </b-card>

  </VerticalLayout>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

const props = defineProps({
  exercises:     { type: Array, default: () => [] },
  missionTypes:  { type: Array, default: () => [] },
  risks:         { type: Array, default: () => [] },
  competencies:  { type: Array, default: () => [] },
  plannedMissions: { type: Array, default: () => [] }, // missions déjà planifiées
})

const form = useForm({
  audit_exercise_id:     null,
  mission_type_id:       null,
  fpm_number:            '',
  title:                 '',
  objective:             '',
  domain:                '',
  reference_document:    '',
  priority:              'moyenne',
  planned_start_date:    '',
  planned_end_date:      '',
  planned_duration_days: 0,
  risk_ids:              [],
  competency_ids:        [],
  competency_levels:     {},
})

const exerciseOptions = computed(() => [
  { value: null, text: '— Choisir exercice —' },
  ...props.exercises.map(ex => ({ value: ex.id, text: `${ex.code} — ${ex.name} (${ex.year})` }))
])

const typeOptions = computed(() => [
  { value: null, text: '— Choisir type —' },
  ...props.missionTypes.map(t => ({ value: t.id, text: `${t.code} — ${t.label}` }))
])

const priorityOptions = [
  { value: 'basse',     text: 'Basse' },
  { value: 'moyenne',   text: 'Moyenne' },
  { value: 'haute',     text: 'Haute' },
  { value: 'critique',  text: 'Critique' },
]

const generatedCode = computed(() => {
  if (!form.audit_exercise_id || !form.mission_type_id) return '(sélectionnez exercice et type)'
  const type = props.missionTypes.find(t => t.id === form.mission_type_id)
  const ex   = props.exercises.find(e => e.id === form.audit_exercise_id)
  if (!type || !ex) return '(données manquantes)'
  const year = ex.year.toString().slice(-2)
  return `${type.code}-XXX-${year}` // simulation – vrai code côté serveur
})

const submit = () => {
  form.post(route('missions.store'), {
    preserveScroll: true,
    onSuccess: () => {
      alert('Mission créée avec succès !')
      router.reload({ only: ['plannedMissions'] })
    },
    onError: (errors) => alert('Erreurs dans le formulaire')
  })
}

const resetForm = () => form.reset()

const truncate = (str, len) => str?.length > len ? str.substring(0,len)+'…' : str
const formatDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR') : '—'
</script>

<style scoped>
/* Réutilisation du style de ton template paramètres */
.form-label {
  color: #0d6efd;
  font-weight: 600;
}
.card-header {
  font-size: 1rem;
}
.form-control-sm, .form-select-sm {
  font-size: 0.8rem;
  height: 28px;
  padding: 0.25rem 0.5rem;
}
</style>