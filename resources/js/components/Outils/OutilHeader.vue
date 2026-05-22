<!-- ════════════════════════════════════════════════════════════════
     OutilHeader.vue — Composant partagé en-tête de tous les outils
     Chemin : resources/js/components/Outils/OutilHeader.vue
════════════════════════════════════════════════════════════════ -->
<template>
  <div class="card border-0 shadow-sm mb-3" :style="`border-left:4px solid ${couleur} !important`">
    <div class="card-body py-2 px-3">
      <div class="d-flex align-items-start gap-2">
        <a :href="backUrl" class="btn btn-outline-secondary btn-sm mt-1">
          <i class="ti ti-arrow-left"></i>
        </a>
        <div class="flex-grow-1">
          <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <span class="badge d-flex align-items-center gap-1 px-2 py-1"
                  :style="`background:${couleur};font-size:.78rem`">
              <i :class="'ti ' + icone"></i> OUTIL {{ codeOutil }}
            </span>
            <code v-if="formCode" class="bg-dark text-white px-2 py-1 rounded small fw-bold">{{ formCode }}</code>
            <span class="badge" :class="vstBadge(statut)">
              <i :class="vstIcon(statut)"></i> {{ vstLbl(statut) }}
            </span>
          </div>
          <h6 class="mb-0 fw-bold">{{ titre }}</h6>
          <small class="text-muted" style="font-size:.68rem">{{ sousTitre }}</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <button v-if="!isLocked && !isNew" class="btn btn-dark btn-sm" :disabled="processing" @click="$emit('sauvegarder')">
            <span v-if="processing" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="ti ti-device-floppy me-1"></i> Enregistrer
          </button>
        </div>
      </div>
    </div>
    <div v-if="statut === 'validated'" class="alert alert-success mb-0 rounded-0 rounded-bottom py-2 px-3 small border-0">
      <i class="ti ti-lock"></i> Outil <strong>validé définitivement</strong> — lecture seule
    </div>
    <div v-else-if="statut === 'in_review'" class="alert alert-info mb-0 rounded-0 rounded-bottom py-2 px-3 small border-0">
      <i class="ti ti-clock"></i> Soumis pour validation<span v-if="canManage"> · DM/CM peut valider ou rejeter</span>
    </div>
  </div>
</template>
<script setup lang="ts">
import { computed } from 'vue'
const props = defineProps<{ codeOutil: string; titre: string; sousTitre?: string; couleur: string; icone: string; formCode?: string; statut: string; canManage?: boolean; backUrl?: string; processing?: boolean }>()
defineEmits(['sauvegarder', 'soumettre', 'valider', 'rejeter'])
const isLocked = computed(() => props.statut === 'validated' || (props.statut === 'in_review' && !props.canManage))
const isNew    = computed(() => !props.formCode)
function vstLbl(s: string)   { return ({ draft: 'Brouillon', in_review: 'En attente', validated: 'Validé ✓' } as any)[s] ?? s }
function vstIcon(s: string)  { return ({ draft: 'ti ti-pencil', in_review: 'ti ti-clock', validated: 'ti ti-circle-check' } as any)[s] ?? 'ti ti-circle' }
function vstBadge(s: string) { return ({ draft: 'bg-secondary', in_review: 'bg-info text-dark', validated: 'bg-success' } as any)[s] ?? 'bg-secondary' }
</script>

<!-- ════════════════════════════════════════════════════════════════
     OutilFooter.vue — Barre d'actions bas de page
     Chemin : resources/js/components/Outils/OutilFooter.vue
════════════════════════════════════════════════════════════════ -->
<template>
  <div class="card border-0 shadow-sm">
    <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div class="d-flex gap-2">
        <button v-if="!isLocked" class="btn btn-outline-secondary btn-sm" :disabled="processing" @click="$emit('cancel')">
          <i class="ti ti-x"></i> Annuler
        </button>
        <button v-if="!isLocked" class="btn btn-dark btn-sm" :disabled="processing" @click="$emit('save')">
          <span v-if="processing" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="ti ti-device-floppy me-1"></i>
          {{ form.id ? 'Mettre à jour' : 'Enregistrer' }}
        </button>
      </div>
      <span v-if="form.id" class="badge bg-success"><i class="ti ti-check"></i> {{ form.code }}</span>
      <div class="d-flex gap-2">
        <button v-if="form.id && form.validation_status === 'draft'"
                class="btn btn-primary btn-sm" :disabled="processing" @click="$emit('soumettre')">
          <i class="ti ti-send me-1"></i> Soumettre
        </button>
        <template v-if="canManage && form.validation_status === 'in_review'">
          <button class="btn btn-success btn-sm" :disabled="processing" @click="$emit('valider')">
            <i class="ti ti-circle-check me-1"></i> Valider
          </button>
          <button class="btn btn-outline-danger btn-sm" :disabled="processing" @click="$emit('rejeter')">
            <i class="ti ti-circle-x me-1"></i> Rejeter
          </button>
        </template>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { computed } from 'vue'
const props = defineProps<{ form: any; processing?: boolean; isLocked?: boolean; canManage?: boolean }>()
defineEmits(['save', 'cancel', 'soumettre', 'valider', 'rejeter'])
const isLocked = computed(() => props.isLocked ?? false)
</script>

<!-- ════════════════════════════════════════════════════════════════
     OutilToast.vue — Notification toast partagée
     Chemin : resources/js/components/Outils/OutilToast.vue
════════════════════════════════════════════════════════════════ -->
<template>
  <Teleport to="body">
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
      <Transition name="fade">
        <div v-if="toast.show" class="toast show align-items-center"
             :class="toast.type === 'success' ? 'text-bg-success' : toast.type === 'info' ? 'text-bg-info' : 'text-bg-danger'" role="alert">
          <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2 small">
              <i :class="toast.type === 'success' ? 'ti ti-circle-check' : toast.type === 'info' ? 'ti ti-info-circle' : 'ti ti-alert-circle'"></i>
              {{ toast.msg }}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" @click="$emit('close')"></button>
          </div>
        </div>
      </Transition>
    </div>
  </Teleport>
</template>
<script setup lang="ts">
defineProps<{ toast: { show: boolean; type: string; msg: string } }>()
defineEmits(['close'])
</script>
<style scoped>
.fade-enter-active,.fade-leave-active{transition:all .2s ease}.fade-enter-from,.fade-leave-to{opacity:0;transform:translateY(6px)}
</style>