<template>
  <div class="wrapper">
    <TopBar />
    <LeftSideBar />

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
import TopBar from '@/layouts/partials/TopBar.vue'
import LeftSideBar from '@/layouts/partials/LeftSideBar.vue'
import Footer from '@/layouts/partials/Footer.vue'
import Customizer from '@/layouts/partials/Customizer.vue'
</script>
