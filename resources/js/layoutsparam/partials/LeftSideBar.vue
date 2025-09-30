<!-- layoutsparam/partials/LeftSideBar.vue -->
<template>
  <nav class="sidenav-menu" aria-label="Navigation latérale">
    <LogoBox />

    <!-- Sidebar Hover Menu Toggle Button -->
    <button class="button-sm-hover" @click="toggleMenuSize" aria-label="Basculer le mode hover">
      <i class="ti ti-circle align-middle"></i>
    </button>

    <!-- Full Sidebar Menu Close Button -->
    <button class="button-close-fullsidebar" @click="closeLeftSideBar" aria-label="Fermer le menu">
      <i class="ti ti-x align-middle"></i>
    </button>

    <SimpleBar>
      <!-- IMPORTANT : VerticalMenu rend et hydrate le menu (dont Organigrammes Entité) -->
      <VerticalMenu />

      <!-- Help Box -->
      <div class="help-box text-center">
        <img :src="coffeeCup" height="90" alt="Helper Icon Image" />
        <h5 class="fw-semibold fs-16 mt-3">DDM PARM</h5>
      </div>

      <div class="clearfix"></div>
    </SimpleBar>
  </nav>
</template>

<script setup lang="ts">
import { onMounted, onBeforeUnmount } from 'vue'
import { storeToRefs } from 'pinia'
import SimpleBar from 'simplebar-vue'
import 'simplebar/dist/simplebar.min.css'

import LogoBox from '@/components/LogoBox.vue'
import { toggleDocumentAttribute } from '@/helpers/other'
import coffeeCup from '@/images/coffee-cup.svg'
import VerticalMenu from '@/layoutsparam/partials/components/VerticalMenu.vue' // Import du nouveau composant
import { useLayoutStore } from '@/stores/layout'

// ✅ Conserver la réactivité Pinia
const layoutStore = useLayoutStore()
const { layout } = storeToRefs(layoutStore)
const { setLeftSideBarSize } = layoutStore

const toggleMenuSize = () => {
  if (layout.value.leftSideBarSize === 'sm-hover-active') {
    return setLeftSideBarSize('sm-hover')
  }
  return setLeftSideBarSize('sm-hover-active')
}

const resize = () => {
  const w = window.innerWidth
  if (w < 770) {
    setLeftSideBarSize('full')
  } else if (w < 1140) {
    setLeftSideBarSize('condensed')
  } else {
    setLeftSideBarSize(
      layout.value.leftSideBarSize === 'condensed' || layout.value.leftSideBarSize === 'full'
        ? 'sm-hover-active'
        : layout.value.leftSideBarSize
    )
  }
}

const onResize = () => resize()

onMounted(() => {
  resize()
  window.addEventListener('resize', onResize, { passive: true })
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', onResize)
})

const closeLeftSideBar = () => {
  toggleDocumentAttribute('class', '')
  const backdrop = document.getElementById('backdrop')
  if (backdrop) document.body.removeChild(backdrop)
}
</script>