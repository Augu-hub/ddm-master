<!-- resources/js/Components/Layout/Sidebar.vue -->
<template>
  <div id="sidebar-menu">
    <ul class="metismenu list-unstyled" id="side-menu">
      <template v-for="item in menu" :key="item.key">
        <li v-if="item.isTitle" class="menu-title">
          {{ item.label }}
        </li>
        
        <li v-else-if="item.children && item.children.length > 0" class="side-nav-item">
          <a href="javascript:void(0);" class="side-nav-link">
            <i :class="item.icon"></i>
            <span>{{ item.label }}</span>
            <span class="menu-arrow"></span>
          </a>
          <ul class="side-nav-second-level" aria-expanded="false">
            <li v-for="child in item.children" :key="child.key">
              <!-- Utiliser DynamicMenu pour les enfants -->
              <DynamicMenu :item="child" />
            </li>
          </ul>
        </li>
        
        <li v-else class="side-nav-item">
          <!-- Item simple -->
          <DynamicMenu :item="item" />
        </li>
      </template>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { menu } from '@/helpers/menu'

// ... reste du code existant ...
</script>