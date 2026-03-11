<!-- layouts/partials/components/HorizontalMenu.vue -->
<template>
  <!--
    IMPORTANT : ce composant ne rend QUE le ul.navbar-nav du menu principal.
    La nav contextuelle des phases (PhaseContextualNav) est gérée
    directement dans TopBar.vue, en dehors de ce ul.
  -->
  <ul class="navbar-nav me-auto">
    <DropDown
      is="li"
      v-for="item in menu"
      :key="item.key"
      class="nav-item hover-dropdown"
    >
      <a
        class="nav-link dropdown-toggle drop-arrow-none"
        :href="item.url || '#'"
        :id="item.url || item.key"
        data-bs-toggle="dropdown"
        role="button"
        aria-haspopup="true"
        aria-expanded="false"
      >
        <span class="menu-icon"><i :class="item.icon"></i></span>
        <span class="menu-text">{{ item.label }}</span>
        <div class="menu-arrow"></div>
      </a>

      <!-- ... tes children / sous-menus existants ... -->
    </DropDown>
  </ul>
</template>

<script setup lang="ts">
import DropDown from '@/components/DropDown.vue'
import { menu } from '@/helpers/menu'

// ✅ Plus de props phasesByType ici — tout est dans phaseNavStore + TopBar
</script>