<!-- resources/js/layoutsparam/ModuleLayout.vue (ton fichier ci-dessus) -->
<template>
  <link rel="stylesheet"
      href="https://unpkg.com/@tabler/icons-webfont@2.47.0/tabler-icons.min.css" />

  <div class="wrapper">
    <TopBar />

    <!-- ⬇️ on injecte les items prêts dans le sidebar -->
    <LeftSideBar :items="items" />

    <div class="page-content">
      <div class="page-container">
        <slot />
      </div>

      <Footer />
      <Customizer />
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { MenuType } from '@/types/layout'
import { buildMenuForModule } from '@/helpers/menu'

import Customizer from '@/layoutsparam/partials/Customizer.vue'
import Footer from '@/layoutsparam/partials/Footer.vue'
import LeftSideBar from '@/layoutsparam/partials/LeftSideBar.vue'
import TopBar from '@/layoutsparam/partials/TopBar.vue'

const items = ref<MenuType[]>([])
const page  = usePage()

function detectModuleCode(): string | null {
  // 1) si le backend pousse le module dans les props Inertia
  const fromProps =
    (page.props as any)?.module?.code ??
    (page.props as any)?.m?.code ??
    null
  if (fromProps) return String(fromProps)

  // 2) sinon: /m/{code}…
  const m = window.location.pathname.match(/^\/m\/([^/?#]+)/)
  return m ? decodeURIComponent(m[1]) : null
}

onMounted(async () => {
  const moduleCode = detectModuleCode()

  // Si un module est détecté → on charge l’arbre de ce module uniquement
  // (Super admin : l’API renvoie tout pour ce module; utilisateurs : visibilité appliquée côté serveur)
  if (moduleCode) {
    items.value = await buildMenuForModule(moduleCode, {
      includeModulesSection: true, // mets false si tu ne veux pas la section “Modules Métier” dans ce layout
    })
  } else {
    // Fallback: charge “tout” (structure globale)
    items.value = await buildMenuForModule('', { includeModulesSection: true })
  }
})
</script>
