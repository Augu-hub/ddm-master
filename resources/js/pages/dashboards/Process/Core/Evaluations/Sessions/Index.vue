<template>
  <VerticalLayout>
    <Head title="Gestion des sessions d'évaluation" />

    <!-- HEADER -->
    <div class="session-header mb-5">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
          <h1 class="fw-bold mb-1">Gestion des Sessions</h1>
          <p class="text-white-75 mb-1">{{ user?.name }} • {{ link?.function_name }} • {{ link?.entity_name }}</p>
          <small>Créer, activer, fermer ou archiver vos sessions d’évaluation</small>
        </div>
        <div>
          <b-button v-if="canCreateSession" variant="success" size="lg" pill @click="showCreateModal = true">
            <i class="ti ti-plus me-2"></i>Nouvelle session
          </b-button>
          <div v-else class="text-center">
            <span class="badge bg-danger fs-5 px-4 py-3">Limite de 5 sessions atteinte</span>
            <p class="text-white-50 small mt-2 mb-0">Archivez ou fermez une session pour en créer une nouvelle</p>
          </div>
        </div>
      </div>
    </div>

    <!-- STATS -->
    <b-row class="g-4 mb-5">
      <b-col md="3" sm="6"><div class="stat-card active"><div class="num">{{ activeSessions.length }}/5</div><div>Sessions actives</div></div></b-col>
      <b-col md="3" sm="6"><div class="stat-card evaluating"><div class="num">{{ evaluatingSession ? '1' : '0' }}/1</div><div>En cours</div></div></b-col>
      <b-col md="3" sm="6"><div class="stat-card closed"><div class="num">{{ closedSessions.length }}</div><div>Fermées</div></div></b-col>
      <b-col md="3" sm="6"><div class="stat-card archived"><div class="num">{{ archivedSessions.length }}</div><div>Archivées</div></div></b-col>
    </b-row>

    <!-- SUCCESS ALERT -->
    <b-alert v-if="successMessage" variant="success" dismissible show class="mb-4">{{ successMessage }}</b-alert>

    <!-- CURRENT SESSION -->
    <div v-if="evaluatingSession" class="current-session mb-5">
      <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
          <div class="icon"><i class="ti ti-star-filled"></i></div>
          <div>
            <h5 class="mb-1">Session en cours : {{ evaluatingSession.name }}</h5>
            <p class="mb-0 text-muted small">
              {{ evaluatingSession.evaluated_count || 0 }}/{{ evaluatingSession.total_processes || 0 }} processus • Score moyen : {{ evaluatingSession.session_avg_score || 0 }}/5
            </p>
          </div>
        </div>
        <router-link :to="{ name: 'process.core.evaluations.criticality.index', params: { session_id: evaluatingSession.id } }"
                     class="btn btn-success fw-bold">Continuer l’évaluation
        </router-link>
      </div>
    </div>

    <!-- ACTIVE SESSIONS -->
    <section class="mb-5">
      <h4 class="mb-3 d-flex align-items-center">
        Sessions Actives <span class="badge bg-success ms-2">{{ activeSessions.length }}/5</span>
      </h4>

      <div v-if="activeSessions.length" class="row g-4">
        <div v-for="s in activeSessions" :key="s.id" class="col-lg-4 col-md-6">
          <div class="session-card" :class="{ active: s.is_active }" :style="{ borderLeft: `6px solid ${s.color}` }">
            <div class="header">
              <div>
                <h6 class="fw-bold">{{ s.name }}</h6>
                <small class="text-muted">{{ formatDate(s.created_at) }}</small>
                <span v-if="s.is_active" class="badge bg-primary ms-2">Active</span>
              </div>
              <span class="badge bg-success">Ouverte</span>
            </div>

            <div class="progress-bar my-3" :style="{ background: s.color }"></div>

            <div v-if="!s.is_active" class="text-center mb-3">
              <b-button variant="primary" @click="activateSession(s)" :disabled="loading" block>Activer cette session</b-button>
            </div>

            <div class="actions">
              <router-link v-if="s.is_active"
                :to="{ name: 'process.core.evaluations.criticality.index', params: { session_id: s.id } }"
                class="btn btn-success w-100 mb-2">Continuer l’évaluation
              </router-link>

              <b-dropdown text="Actions" variant="outline-secondary" block class="mt-2">
                <b-dropdown-item @click="openDuplicateModal(s)">Dupliquer</b-dropdown-item>
                <b-dropdown-item @click="confirmCloseSession(s)">Fermer</b-dropdown-item>
                <b-dropdown-item @click="confirmArchiveSession(s)">Archiver</b-dropdown-item>
                <b-dropdown-divider />
                <b-dropdown-item class="text-danger" @click="confirmDeleteSession(s)">Supprimer</b-dropdown-item>
              </b-dropdown>
            </div>

            <div class="footer">
              <span>{{ s.evaluated_count || 0 }}/{{ s.total_processes || 0 }} processus</span>
              <span>Score : {{ s.session_avg_score || 0 }}/5</span>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-5 bg-light rounded-4">
        <i class="ti ti-inbox display-4 text-muted mb-3"></i>
        <p class="text-muted mb-3">Aucune session active</p>
        <b-button variant="success" @click="showCreateModal = true" v-if="canCreateSession">Créer ma première session</b-button>
      </div>
    </section>

    <!-- CLOSED & ARCHIVED SESSIONS (inchangées pour brièveté) -->
    <!-- Tu peux remettre les sections "Fermées" et "Archivées" du code précédent ici -->

    <!-- MODAL CREATE -->
    <b-modal v-model="showCreateModal" title="Créer une session" centered hide-footer>
      <b-form @submit.prevent="createSession">
        <b-form-group label="Nom de la session">
          <b-form-input v-model="newSessionForm.name" placeholder="Ex: Audit Q1 2025" required autofocus />
        </b-form-group>
        <b-form-group label="Couleur d’identification" class="mt-3">
          <div class="d-flex gap-2 flex-wrap">
            <div v-for="c in sessionColors" :key="c" class="color-swatch"
                 :class="{ selected: newSessionForm.color === c }"
                 :style="{ background: c }" @click="newSessionForm.color = c"></div>
          </div>
        </b-form-group>
        <div class="d-flex gap-2 mt-4 justify-content-end">
          <b-button variant="secondary" @click="showCreateModal = false">Annuler</b-button>
          <b-button type="submit" variant="success" :disabled="!newSessionForm.name.trim()">Créer</b-button>
        </div>
      </b-form>
    </b-modal>

    <!-- MODAL DUPLICATE -->
    <b-modal v-model="showDuplicateModal" title="Dupliquer la session" centered hide-footer>
      <p><strong>Source :</strong> {{ sessionToDuplicate?.name }}</p>
      <b-form @submit.prevent="duplicateSession">
        <b-form-group label="Nouveau nom">
          <b-form-input v-model="duplicateForm.name" :placeholder="`${sessionToDuplicate?.name} (copie)`" required />
        </b-form-group>
        <div class="d-flex gap-2 mt-3 justify-content-end">
          <b-button variant="secondary" @click="showDuplicateModal = false">Annuler</b-button>
          <b-button type="submit" variant="primary">Dupliquer</b-button>
        </div>
      </b-form>
    </b-modal>
  </VerticalLayout>
</template>

<script setup>
import { Head } from "@inertiajs/vue3"
import VerticalLayout from "@/layouts/VerticalLayout.vue"


import axios from "axios"
import { ref, computed, onMounted } from "vue"

const props = defineProps({
  user: Object,
  link: Object,
  sessions: Array,
  processes: Array,
})

const sessions = ref(props.sessions || [])
const showCreateModal = ref(false)
const showDuplicateModal = ref(false)
const sessionToDuplicate = ref(null)
const successMessage = ref("")
const loading = ref(false)

const newSessionForm = ref({ name: "", color: "#10b981" })
const duplicateForm = ref({ name: "" })

const sessionColors = ["#10b981","#3b82f6","#f59e0b","#ef4444","#8b5cf6","#ec4899","#06b6d4"]

const activeSessions = computed(() => sessions.value.filter(s => s.status === "open"))
const closedSessions = computed(() => sessions.value.filter(s => s.status === "closed"))
const archivedSessions = computed(() => sessions.value.filter(s => s.status === "archived"))
const canCreateSession = computed(() => activeSessions.value.length < 5)
const evaluatingSession = computed(() => sessions.value.find(s => s.is_active))

const formatDate = (date) => new Date(date).toLocaleString("fr-FR", {
  year: "numeric", month: "short", day: "numeric", hour: "2-digit", minute: "2-digit"
})

const createSession = async () => {
  if (!newSessionForm.value.name.trim()) return
  try {
    loading.value = true
    const { data } = await axios.post(route("process.core.sessions.create"), {
      entity_id: props.link.entity_id,
      function_id: props.link.function_id,
      ...newSessionForm.value
    })
    sessions.value.push({
      ...newSessionForm.value,
      id: data.session_id,
      status: "open",
      is_active: false,
      created_at: new Date(),
      evaluated_count: 0,
      session_avg_score: 0,
      total_processes: props.processes?.length || 0
    })
    showCreateModal.value = false
    newSessionForm.value = { name: "", color: "#10b981" }
    successMessage.value = "Session créée avec succès"
    setTimeout(() => successMessage.value = "", 4000)
  } catch (e) {
    alert("Erreur création : " + (e.response?.data?.error || e.message))
  } finally {
    loading.value = false
  }
}

const activateSession = async (s) => {
  if (loading.value) return
  loading.value = true
  try {
    await axios.post(route("process.core.sessions.activate"), { session_id: s.id })
    sessions.value.forEach(x => x.is_active = x.id === s.id)
    successMessage.value = "Session activée"
    setTimeout(() => location.reload(), 600)
  } catch (e) {
    alert("Erreur activation")
  } finally {
    loading.value = false
  }
}

const openDuplicateModal = (s) => {
  sessionToDuplicate.value = s
  duplicateForm.value.name = ""
  showDuplicateModal.value = true
}

const duplicateSession = async () => {
  try {
    const { data } = await axios.post(route("process.core.sessions.duplicate"), {
      source_session_id: sessionToDuplicate.value.id,
      name: duplicateForm.value.name || `${sessionToDuplicate.value.name} (copie)`
    })
    sessions.value.push({
      ...sessionToDuplicate.value,
      id: data.session_id,
      name: duplicateForm.value.name || `${sessionToDuplicate.value.name} (copie)`,
      is_active: false
    })
    showDuplicateModal.value = false
    successMessage.value = "Session dupliquée avec succès"
    setTimeout(() => successMessage.value = "", 4000)
  } catch (e) {
    alert("Erreur duplication : " + (e.response?.data?.error || e.message))
  }
}

const confirmCloseSession = (s) => confirm("Fermer la session ?") && axios.post(route("process.core.sessions.close"), { session_id: s.id }).then(() => {
  s.status = "closed"; s.is_active = false; successMessage.value = "Session fermée"
})

const confirmArchiveSession = (s) => confirm("Archiver la session ?") && axios.post(route("process.core.sessions.archive"), { session_id: s.id }).then(() => {
  s.status = "archived"; s.is_active = false; successMessage.value = "Session archivée"
})

const confirmDeleteSession = (s) => confirm("Supprimer définitivement ? Cette action est irréversible.") && axios.post(route("process.core.sessions.delete"), { session_id: s.id }).then(() => {
  sessions.value = sessions.value.filter(x => x.id !== s.id)
  successMessage.value = "Session supprimée"
})

onMounted(() => evaluatingSession.value && console.log("Session active :", evaluatingSession.value.name))
</script>

<style scoped>
.session-header{background:linear-gradient(135deg,#1e40af,#172554);color:#fff;padding:2rem;border-radius:1rem}
.stat-card{background:#fff;border-radius:1rem;padding:1.5rem;box-shadow:0 4px 15px rgba(0,0,0,.08);text-align:center;transition:.3s;border-left:5px solid}
.stat-card:hover{transform:translateY(-8px);box-shadow:0 12px 25px rgba(0,0,0,.12)}
.stat-card.active{border-left-color:#10b981;background:#ecfdf5}
.stat-card.evaluating{border-left-color:#3b82f6;background:#eff6ff}
.stat-card.closed{border-left-color:#f59e0b;background:#fffbeb}
.stat-card.archived{border-left-color:#6b7280;background:#f9fafb}
.stat-card .num{font-size:2.8rem;font-weight:800;margin-bottom:.5rem}
.current-session{background:#ecfdf5;border:2px solid #10b981;border-radius:1rem;padding:1.5rem}
.current-session .icon{width:50px;height:50px;background:#10b981;color:#fff;border-radius:50%;display:grid;place-items:center;font-size:1.5rem;margin-right:1rem}
.session-card{background:#fff;border-radius:1rem;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);transition:.3s;height:100%;display:flex;flex-direction:column}
.session-card.active{box-shadow:0 0 0 3px rgba(59,130,246,.3)}
.session-card.closed{opacity:.85;background:#f8fafc}
.session-card.archived{opacity:.7;background:#f3f4f6}
.session-card .header{display:flex;justify-content:space-between;align-items:start;padding:1.25rem 1.5rem 0}
.progress-bar{height:4px;width:100%}
.session-card .actions{flex:1;display:flex;flex-direction:column;justify-content:end;padding:0 1.5rem 1.5rem}
.session-card .footer{display:flex;justify-content:space-between;font-size:.9rem;padding:1rem 1.5rem;background:#f9fafb;border-top:1px solid #eee}
.color-swatch{width:40px;height:40px;border-radius:8px;cursor:pointer;border:3px solid #ddd;transition:.2s}
.color-swatch:hover{transform:scale(1.15)}
.color-swatch.selected{border-color:#000;box-shadow:0 0 0 3px #3b82f6}
</style>