<template>
  <VerticalLayoutAudit>
    <RapportWordModal
      v-model:show="show"
      :mission-id="missionId"
      :url-data="urlData"
      :url-download="urlDownload"
      :url-html="urlHtml"
      :url-save="urlSave"
      @closed="goBack"
    />

    <div v-if="!show" class="rap-empty">
      <div class="rap-spin"></div>
      <p>Redirection en cours…</p>
    </div>
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/Layouts/VerticalLayoutAudit.vue'
import RapportWordModal from '@/Components/RapportWordModal.vue'

const props = defineProps<{
  missionId:       number | string
  missionCode:     string
  missionLibelle:  string
  backUrl:         string
  urlData:         string
  urlDownload:     string
  urlHtml:         string        // ← prop ajoutée
  urlSave?:        string
}>()

const show = ref(false)

onMounted(() => {
  setTimeout(() => { show.value = true }, 50)
})

function goBack() {
  if (props.backUrl) {
    router.visit(props.backUrl)
  } else {
    history.back()
  }
}
</script>

<style scoped>
.rap-empty {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; height: 100vh; gap: .6rem; color: #64748b;
}
.rap-spin {
  width: 32px; height: 32px;
  border: 3px solid #e2e8f0; border-top-color: #1e40af;
  border-radius: 50%; animation: sp .7s linear infinite;
}
@keyframes sp { to { transform: rotate(360deg); } }
</style>