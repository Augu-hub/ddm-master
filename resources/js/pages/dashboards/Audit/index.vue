<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 p-6">
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- 📌 HEADER + SESSION ACTIVE                                        -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->

    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 flex items-center gap-3">
            <span class="text-2xl">⚠️</span> Gestion des Risques d'Audit
          </h1>
          <p class="text-slate-600 mt-2">Identifiez, évaluez et maîtrisez les risques</p>
        </div>
        
        <!-- Session Active Affichage -->
        <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-blue-500">
          <p class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Session Active</p>
          <p class="text-lg font-bold text-slate-900">{{ activeSession.entity_name }}</p>
          <p class="text-sm text-slate-600">{{ activeSession.year }}</p>
          <button
            @click="showSwitchModal = true"
            class="mt-3 text-xs bg-blue-50 text-blue-600 hover:bg-blue-100 px-3 py-1 rounded font-semibold transition"
          >
            🔄 Changer Session
          </button>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- 📊 STATISTIQUES SESSION                                           -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <!-- Total Risques -->
        <div class="bg-white rounded-lg shadow p-6 border-t-4 border-blue-500">
          <p class="text-xs uppercase text-slate-500 font-semibold">Risques Créés</p>
          <p class="text-3xl font-bold text-blue-600 mt-2">
            {{ activeSession.total_risks_created }}
          </p>
        </div>

        <!-- Risques Évalués -->
        <div class="bg-white rounded-lg shadow p-6 border-t-4 border-green-500">
          <p class="text-xs uppercase text-slate-500 font-semibold">Risques Évalués</p>
          <p class="text-3xl font-bold text-green-600 mt-2">
            {{ activeSession.total_risks_evaluated }}
          </p>
        </div>

        <!-- Taux d'Évaluation -->
        <div class="bg-white rounded-lg shadow p-6 border-t-4 border-purple-500">
          <p class="text-xs uppercase text-slate-500 font-semibold">Taux d'Évaluation</p>
          <p class="text-3xl font-bold text-purple-600 mt-2">
            {{
              activeSession.total_risks_created > 0
                ? Math.round(
                    (activeSession.total_risks_evaluated / activeSession.total_risks_created) * 100
                  )
                : 0
            }}%
          </p>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- ➕ BOUTON CRÉER RISQUE + FILTRE                                    -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->

      <div class="flex items-center justify-between mb-8 bg-white p-4 rounded-lg shadow">
        <div class="flex items-center gap-4">
          <button
            @click="showCreateForm = !showCreateForm"
            class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-4 py-2 rounded-lg hover:shadow-lg transition font-semibold flex items-center gap-2"
          >
            <span>➕</span> Créer Risque
          </button>

          <input
            v-model="filterText"
            type="text"
            placeholder="🔍 Filtrer risques..."
            class="px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <select
          v-model="sortBy"
          class="px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="created_at_desc">Récemment créés</option>
          <option value="criticality_desc">Criticité décroissante</option>
          <option value="criticality_asc">Criticité croissante</option>
        </select>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- ➕ FORMULAIRE CRÉER RISQUE                                         -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->

      <div v-if="showCreateForm" class="bg-white rounded-lg shadow-lg p-8 mb-8 border-l-4 border-blue-500">
        <h2 class="text-xl font-bold text-slate-900 mb-6 flex items-center gap-2">
          <span>📝</span> Nouveau Risque
          <span class="text-sm font-normal text-slate-600">
            (Session: {{ activeSession.entity_name }} - {{ activeSession.year }})
          </span>
        </h2>

        <form @submit.prevent="createRisk" class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Process Area -->
          <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-slate-700 mb-2">
              🏢 Domaine/Processus
            </label>
            <input
              v-model="formData.process_area"
              type="text"
              placeholder="Ex: Paie, Trésorerie, ..."
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            />
          </div>

          <!-- Risk Description -->
          <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-slate-700 mb-2">
              📋 Description Risque
            </label>
            <textarea
              v-model="formData.risk_description"
              placeholder="Décrivez le risque identifié..."
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 h-24"
              required
            ></textarea>
          </div>

          <!-- Risk Type -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">
              🏷️ Type Risque
            </label>
            <select
              v-model="formData.risk_type_id"
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            >
              <option value="">Sélectionner...</option>
              <option v-for="type in riskTypes" :key="type.id" :value="type.id">
                {{ type.name }}
              </option>
            </select>
          </div>

          <!-- Frequency Level -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">
              📊 Fréquence
            </label>
            <select
              v-model="formData.frequency_level_id"
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            >
              <option value="">Sélectionner...</option>
              <option v-for="freq in frequencyLevels" :key="freq.id" :value="freq.id">
                {{ freq.name }}
              </option>
            </select>
          </div>

          <!-- Impact Level -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">
              ⚡ Impact
            </label>
            <select
              v-model="formData.impact_level_id"
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            >
              <option value="">Sélectionner...</option>
              <option v-for="impact in impactLevels" :key="impact.id" :value="impact.id">
                {{ impact.name }}
              </option>
            </select>
          </div>

          <!-- Existing Controls -->
          <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-slate-700 mb-2">
              ✅ Contrôles Existants
            </label>
            <textarea
              v-model="formData.existing_controls"
              placeholder="Contrôles déjà en place..."
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 h-20"
            ></textarea>
          </div>

          <!-- Potential Controls -->
          <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-slate-700 mb-2">
              🛡️ Contrôles Potentiels
            </label>
            <textarea
              v-model="formData.potential_controls"
              placeholder="Contrôles à mettre en place..."
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 h-20"
            ></textarea>
          </div>

          <!-- Risk Owner -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">
              👤 Responsable Risque
            </label>
            <input
              v-model="formData.risk_owner"
              type="text"
              placeholder="Nom du responsable"
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <!-- Is Critical -->
          <div class="flex items-center gap-3 pt-6">
            <input
              v-model="formData.is_critical"
              type="checkbox"
              id="is_critical"
              class="w-5 h-5 text-red-600 rounded focus:ring-2 focus:ring-red-500"
            />
            <label for="is_critical" class="text-sm font-semibold text-slate-700">
              🚨 Risque Critique
            </label>
          </div>

          <!-- Submit Buttons -->
          <div class="md:col-span-2 flex gap-3 justify-end pt-4 border-t">
            <button
              type="button"
              @click="showCreateForm = false"
              class="px-6 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 transition"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="isCreating"
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50"
            >
              {{ isCreating ? "Création..." : "Créer Risque" }}
            </button>
          </div>
        </form>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- 📋 LISTE RISQUES                                                  -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->

      <div v-if="filteredAndSortedRisks.length === 0" class="bg-white rounded-lg shadow p-8 text-center">
        <p class="text-slate-500 text-lg">📭 Aucun risque pour cette session</p>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="risk in filteredAndSortedRisks"
          :key="risk.id"
          class="bg-white rounded-lg shadow-md hover:shadow-lg transition overflow-hidden"
        >
          <div class="flex">
            <!-- Barre couleur criticité -->
            <div
              :class="{
                'bg-red-500': risk.color === 'red',
                'bg-orange-500': risk.color === 'orange',
                'bg-yellow-500': risk.color === 'yellow',
                'bg-green-500': risk.color === 'green',
              }"
              class="w-2"
            ></div>

            <!-- Contenu Risque -->
            <div class="flex-1 p-6">
              <div class="flex items-start justify-between mb-4">
                <div>
                  <h3 class="text-lg font-bold text-slate-900">
                    {{ risk.process_area }}
                  </h3>
                  <p class="text-sm text-slate-600 mt-1">
                    {{ risk.risk_description }}
                  </p>
                </div>

                <!-- Criticité Badges -->
                <div class="flex gap-2 ml-4">
                  <span
                    :class="{
                      'bg-red-100 text-red-800': risk.color === 'red',
                      'bg-orange-100 text-orange-800': risk.color === 'orange',
                      'bg-yellow-100 text-yellow-800': risk.color === 'yellow',
                      'bg-green-100 text-green-800': risk.color === 'green',
                    }"
                    class="px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap"
                  >
                    Brute: {{ risk.brute_criticality }}
                  </span>
                  <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 whitespace-nowrap">
                    Nette: {{ risk.net_criticality }}
                  </span>
                </div>
              </div>

              <!-- Détails -->
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4 text-sm">
                <div>
                  <p class="text-xs uppercase text-slate-500 font-semibold">Type</p>
                  <p class="text-slate-900 font-medium">{{ risk.risk_type }}</p>
                </div>
                <div>
                  <p class="text-xs uppercase text-slate-500 font-semibold">Fréquence</p>
                  <p class="text-slate-900 font-medium">{{ risk.frequency_level }}</p>
                </div>
                <div>
                  <p class="text-xs uppercase text-slate-500 font-semibold">Impact</p>
                  <p class="text-slate-900 font-medium">{{ risk.impact_level }}</p>
                </div>
                <div>
                  <p class="text-xs uppercase text-slate-500 font-semibold">Responsable</p>
                  <p class="text-slate-900 font-medium">{{ risk.risk_owner || "—" }}</p>
                </div>
              </div>

              <!-- Contrôles -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 text-sm">
                <div v-if="risk.existing_controls" class="bg-green-50 p-3 rounded">
                  <p class="text-xs uppercase text-green-700 font-semibold mb-1">✅ Contrôles Existants</p>
                  <p class="text-slate-700">{{ risk.existing_controls }}</p>
                </div>
                <div v-if="risk.potential_controls" class="bg-blue-50 p-3 rounded">
                  <p class="text-xs uppercase text-blue-700 font-semibold mb-1">🛡️ Contrôles Potentiels</p>
                  <p class="text-slate-700">{{ risk.potential_controls }}</p>
                </div>
              </div>

              <!-- Évaluation + Actions -->
              <div class="flex items-center justify-between pt-4 border-t">
                <div class="flex items-center gap-4">
                  <!-- Status Évaluation -->
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input
                      type="checkbox"
                      v-model="risk.is_evaluated"
                      @change="toggleEvaluation(risk)"
                      class="w-5 h-5 text-green-600 rounded focus:ring-2 focus:ring-green-500"
                    />
                    <span class="text-sm font-semibold text-slate-700">
                      {{ risk.is_evaluated ? "✅ Évalué" : "⏳ Non Évalué" }}
                    </span>
                  </label>

                  <span v-if="risk.is_critical" class="text-xs bg-red-100 text-red-800 px-3 py-1 rounded-full font-semibold">
                    🚨 Critique
                  </span>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                  <button
                    @click="editRisk(risk)"
                    class="px-3 py-1 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded text-sm font-semibold transition"
                  >
                    ✏️ Éditer
                  </button>
                  <button
                    @click="deleteRisk(risk.id)"
                    class="px-3 py-1 bg-red-50 text-red-600 hover:bg-red-100 rounded text-sm font-semibold transition"
                  >
                    🗑️ Supprimer
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- 🔄 MODAL SWITCH SESSION                                            -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->

    <div
      v-if="showSwitchModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="showSwitchModal = false"
    >
      <div class="bg-white rounded-lg shadow-2xl max-w-md w-full p-8">
        <h2 class="text-2xl font-bold text-slate-900 mb-6 flex items-center gap-2">
          <span>🔄</span> Changer Session Active
        </h2>

        <p class="text-slate-600 mb-6">Sélectionnez une nouvelle session d'audit:</p>

        <div class="space-y-3 max-h-64 overflow-y-auto mb-6">
          <label
            v-for="session in allSessions"
            :key="session.id"
            class="flex items-center gap-3 p-4 border-2 rounded-lg cursor-pointer hover:bg-slate-50 transition"
            :class="{
              'border-blue-500 bg-blue-50': activeSession.id === session.id,
              'border-slate-300': activeSession.id !== session.id,
            }"
          >
            <input
              type="radio"
              :value="session.id"
              v-model="selectedSessionId"
              name="session"
              class="w-4 h-4 text-blue-600"
            />
            <div>
              <p class="font-semibold text-slate-900">{{ session.label }}</p>
              <p class="text-xs text-slate-500">{{ session.year }}</p>
            </div>
          </label>
        </div>

        <div class="flex gap-3">
          <button
            @click="showSwitchModal = false"
            class="flex-1 px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 transition font-semibold"
          >
            Annuler
          </button>
          <button
            @click="switchSession"
            :disabled="!selectedSessionId || isSwitching"
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 font-semibold"
          >
            {{ isSwitching ? "Changement..." : "Appliquer" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";

// ════════════════════════════════════════════════════════════════════════════
// 📌 PROPS & STATE
// ════════════════════════════════════════════════════════════════════════════

const props = defineProps({
  activeSession: Object,
  risks: Array,
  riskTypes: Array,
  frequencyLevels: Array,
  impactLevels: Array,
  allSessions: Array,
});

// State local
const showCreateForm = ref(false);
const showSwitchModal = ref(false);
const isCreating = ref(false);
const isSwitching = ref(false);
const filterText = ref("");
const sortBy = ref("created_at_desc");
const selectedSessionId = ref(null);

// Données form créer risque
const formData = ref({
  process_area: "",
  risk_description: "",
  risk_type_id: "",
  frequency_level_id: "",
  impact_level_id: "",
  existing_controls: "",
  potential_controls: "",
  risk_owner: "",
  is_critical: false,
});

// State risques (mutable)
const risks_state = ref(props.risks || []);

// ════════════════════════════════════════════════════════════════════════════
// 🔄 COMPUTED
// ════════════════════════════════════════════════════════════════════════════

const filteredAndSortedRisks = computed(() => {
  let result = risks_state.value;

  // 🔍 Filtre
  if (filterText.value) {
    const search = filterText.value.toLowerCase();
    result = result.filter(
      (r) =>
        r.process_area.toLowerCase().includes(search) ||
        r.risk_description.toLowerCase().includes(search) ||
        r.risk_type.toLowerCase().includes(search)
    );
  }

  // 📊 Tri
  if (sortBy.value === "criticality_desc") {
    result.sort((a, b) => b.brute_criticality - a.brute_criticality);
  } else if (sortBy.value === "criticality_asc") {
    result.sort((a, b) => a.brute_criticality - b.brute_criticality);
  } else {
    result.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  }

  return result;
});

// ════════════════════════════════════════════════════════════════════════════
// 🎬 METHODS
// ════════════════════════════════════════════════════════════════════════════

const createRisk = async () => {
  isCreating.value = true;

  try {
    const response = await fetch("/api/m/risk.core", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify(formData.value),
    });

    const data = await response.json();

    if (data.success) {
      risks_state.value.unshift(data.risk);
      
      // Réinitialise form
      formData.value = {
        process_area: "",
        risk_description: "",
        risk_type_id: "",
        frequency_level_id: "",
        impact_level_id: "",
        existing_controls: "",
        potential_controls: "",
        risk_owner: "",
        is_critical: false,
      };
      
      showCreateForm.value = false;
      
      // Notification
      alert("✅ Risque créé avec succès!");
    }
  } catch (error) {
    console.error("Create error:", error);
    alert("❌ Erreur lors de la création");
  } finally {
    isCreating.value = false;
  }
};

const editRisk = (risk) => {
  // Simplifié: juste pour démo
  alert("Edit risque " + risk.id + " - À implémenter");
};

const toggleEvaluation = async (risk) => {
  try {
    const response = await fetch(`/api/m/risk.core/${risk.id}`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({
        ...risk,
      }),
    });

    const data = await response.json();
    if (data.success) {
      Object.assign(risk, data.risk);
    }
  } catch (error) {
    console.error("Toggle error:", error);
    alert("❌ Erreur lors de la mise à jour");
  }
};

const deleteRisk = async (riskId) => {
  if (!confirm("Êtes-vous sûr de vouloir supprimer ce risque?")) return;

  try {
    const response = await fetch(`/api/m/risk.core/${riskId}`, {
      method: "DELETE",
      headers: {
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
      },
    });

    const data = await response.json();
    if (data.success) {
      risks_state.value = risks_state.value.filter((r) => r.id !== riskId);
      alert("✅ Risque supprimé");
    }
  } catch (error) {
    console.error("Delete error:", error);
    alert("❌ Erreur lors de la suppression");
  }
};

const switchSession = async () => {
  isSwitching.value = true;

  try {
    const response = await fetch(`/api/m/risk.core/switch-session/${selectedSessionId.value}`, {
      method: "PUT",
      headers: {
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
      },
    });

    const data = await response.json();

    if (data.success) {
      // Met à jour state
      Object.assign(props.activeSession, data.session);
      risks_state.value = data.risks;
      
      showSwitchModal.value = false;
      selectedSessionId.value = null;
      
      alert("✅ Session changée avec succès!");
    }
  } catch (error) {
    console.error("Switch error:", error);
    alert("❌ Erreur lors du changement de session");
  } finally {
    isSwitching.value = false;
  }
};
</script>