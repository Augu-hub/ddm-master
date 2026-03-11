<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 p-6">
    <div class="max-w-6xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-4xl font-bold text-slate-900 mb-2">
          ✍️ Demande de Mission d'Audit
        </h1>
        <p class="text-slate-600">
          Entité: <span class="font-semibold">{{ entity.name }}</span>
        </p>
      </div>

      <div class="grid lg:grid-cols-4 gap-6">
        <!-- Entités auditées (gauche) -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow-md p-4 border border-slate-200 sticky top-6">
            <h3 class="font-bold text-slate-900 mb-4">🏢 Entités auditées</h3>
            <div class="space-y-2">
              <label v-for="ent in entities" :key="ent.id" class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="form.audited_entity_ids"
                  type="checkbox"
                  :value="ent.id"
                  class="w-4 h-4 text-blue-600 rounded"
                />
                <span class="text-sm text-slate-700">{{ ent.name }}</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Formulaire (centre) -->
        <div class="lg:col-span-2">
          <form @submit.prevent="submitForm" class="bg-white rounded-lg shadow-md p-6 border border-slate-200 space-y-5">
            <div>
              <label class="block text-sm font-bold text-slate-900 mb-2">
                Source de mission *
              </label>
              <select
                v-model="form.mission_source_id"
                required
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">-- Sélectionner --</option>
                <option v-for="source in missionSources" :key="source.id" :value="source.id">
                  {{ source.label }} ({{ source.code }})
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-bold text-slate-900 mb-2">
                Type de mission
              </label>
              <input
                v-model="form.mission_type"
                type="text"
                placeholder="Ex: ASS-ADC"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <div>
              <label class="block text-sm font-bold text-slate-900 mb-2">
                Objectif de la mission *
              </label>
              <input
                v-model="form.mission_objective"
                type="text"
                required
                placeholder="Objectif principal"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <div>
              <label class="block text-sm font-bold text-slate-900 mb-2">
                Description
              </label>
              <textarea
                v-model="form.description"
                rows="3"
                placeholder="Description détaillée..."
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <div>
              <label class="block text-sm font-bold text-slate-900 mb-2">
                Préoccupation
              </label>
              <textarea
                v-model="form.concern"
                rows="2"
                placeholder="Préoccupation exprimée..."
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <div>
              <label class="block text-sm font-bold text-slate-900 mb-2">
                Résultat attendu
              </label>
              <textarea
                v-model="form.result"
                rows="2"
                placeholder="Résultats attendus..."
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <div>
              <label class="block text-sm font-bold text-slate-900 mb-2">
                Périmètre d'audit
              </label>
              <input
                v-model="form.audit_scope"
                type="text"
                placeholder="Ex: Direction X, Filiale Y..."
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <div>
              <label class="block text-sm font-bold text-slate-900 mb-2">
                Fréquence *
              </label>
              <select
                v-model="form.frequency"
                required
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option>Ponctuelle</option>
                <option>Tous les ans</option>
                <option>Tous les 2 ans</option>
                <option>Tous les 3 ans</option>
                <option>Tous les 4 ans</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-bold text-slate-900 mb-2">
                Date proposée
              </label>
              <input
                v-model="form.proposed_date"
                type="date"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <button
              type="submit"
              class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200"
            >
              ✓ Créer la demande
            </button>
          </form>
        </div>

        <!-- Dropdowns (droite) -->
        <div class="lg:col-span-1 space-y-4">
          <div class="bg-white rounded-lg shadow-md p-4 border border-slate-200">
            <h3 class="font-bold text-slate-900 mb-3">🔗 Lier à un risque</h3>
            <select
              v-model="form.related_risk_id"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Aucun</option>
              <option v-for="risk in risks" :key="risk.id" :value="risk.id">
                {{ risk.code }}: {{ risk.label.substring(0, 20) }}...
              </option>
            </select>
          </div>

          <div class="bg-white rounded-lg shadow-md p-4 border border-slate-200">
            <h3 class="font-bold text-slate-900 mb-3">📋 Lier à un processus</h3>
            <select
              v-model="form.related_process_id"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Aucun</option>
              <option v-for="proc in processes" :key="proc.id" :value="proc.id">
                {{ proc.code }}: {{ proc.name }}
              </option>
            </select>
          </div>

          <div class="bg-white rounded-lg shadow-md p-4 border border-slate-200">
            <h3 class="font-bold text-slate-900 mb-3">👤 Lier à une fonction</h3>
            <select
              v-model="form.related_function_id"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Aucune</option>
              <option v-for="func in functions" :key="func.id" :value="func.id">
                {{ func.character || func.name }}
              </option>
            </select>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'

defineProps({
  entity: Object,
  missionSources: Array,
  entities: Array,
  processes: Array,
  functions: Array,
  risks: Array,
})

const form = useForm({
  mission_source_id: '',
  mission_type: '',
  mission_objective: '',
  description: '',
  concern: '',
  result: '',
  audit_scope: '',
  related_risk_id: null,
  related_process_id: null,
  related_function_id: null,
  frequency: 'Ponctuelle',
  proposed_date: '',
  audited_entity_ids: []
})

const submitForm = () => {
  form.post(route('audit.mission-request.store'), {
    onSuccess: () => form.reset()
  })
}
</script>