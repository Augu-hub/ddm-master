<!-- layouts/partials/TopBar.vue -->
<template>
  <!-- La topbar principale -->
  <header class="topnav">
    <nav class="navbar navbar-expand-lg">
      <nav class="page-container">
        <b-collapse class="navbar-collapse" id="navbar">
          <HorizontalMenu />
        </b-collapse>
      </nav>
    </nav>

    <!--
      Sous-barre contextuelle des phases.
      Elle s'affiche SOUS la ligne du menu principal,
      à l'intérieur du même <header> pour rester dans le flux sticky.
      Rendue uniquement quand le store contient des phases (page MissionPhases).
    -->
    <PhaseContextualNav />
  </header>
</template>

<script setup lang="ts">
import HorizontalMenu from '@/layouts/partials/components/HorizontalMenu.vue'
import PhaseContextualNav from '@/layouts/partials/components/PhaseContextualNav.vue'
</script>