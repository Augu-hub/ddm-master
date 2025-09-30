<!-- layoutsparam/partials/components/VerticalMenu.vue -->
<template>
  <ul class="side-nav">
    <!-- État de chargement -->
    <li v-if="loading" class="side-nav-item">
      <a class="side-nav-link text-info">
        <span class="menu-icon"><i class="ti ti-loader"></i></span>
        <span class="menu-text">Chargement des entités...</span>
      </a>
    </li>

    <!-- État d'erreur -->
    <li v-else-if="error" class="side-nav-item">
      <a class="side-nav-link text-danger">
        <span class="menu-icon"><i class="ti ti-alert-triangle"></i></span>
        <span class="menu-text">Erreur chargement</span>
      </a>
    </li>

    <!-- État chargé -->
    <li v-else class="side-nav-item">
      <a class="side-nav-link text-success">
        <span class="menu-icon"><i class="ti ti-check"></i></span>
        <span class="menu-text">✅ {{ entitiesCount }} entités</span>
      </a>
    </li>

    <!-- Menu normal -->
    <template v-for="section in menuItems" :key="section.key || section.label">
      <li v-if="section.isTitle" class="side-nav-title">{{ section.label }}</li>

      <template v-else-if="section.children">
        <template v-for="child in section.children" :key="child.key">
          <!-- child avec enfants -->
          <li v-if="child.children" class="side-nav-item" :class="{ active: parent && child.key === parent.key }">
            <a class="side-nav-link" v-b-toggle="child.key" role="button">
              <span class="menu-icon"><i :class="child.icon"></i></span>
              <span class="menu-text">{{ child.label }}</span>
              <span class="menu-arrow"></span>
            </a>

            <b-collapse :id="child.key" :visible="child.key === parent?.key">
              <ul class="sub-menu">
                <li v-for="sub in child.children" :key="sub.key" class="side-nav-item" :class="{ active: sub.url === currentUrl }">
                  <Link :href="sub.url!" :target="sub.target" class="side-nav-link" :class="{ active: sub.url === currentUrl }">
                    <span class="menu-text">{{ sub.label }}</span>
                  </Link>
                </li>
              </ul>
            </b-collapse>
          </li>

          <!-- child sans enfants -->
          <li v-else class="side-nav-item" :class="{ active: child.url === currentUrl }">
            <Link :href="child.url!" :target="child.target" class="side-nav-link" :class="{ active: child.url === currentUrl }">
              <span class="menu-icon"><i :class="child.icon"></i></span>
              <span class="menu-text">{{ child.label }}</span>
            </Link>
          </li>
        </template>
      </template>
    </template>
  </ul>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { menu, updateMenuWithEntities } from '@/helpers/menu'
import { getActiveItem, getParentOfActiveItem } from '@/layouts/partials/components/menu'
import type { MenuType } from '@/types/layout'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const currentUrl = page.url

// États pour le chargement côté client
const entitiesData = ref<any[]>([])
const loading = ref(false)
const error = ref(false)

const entitiesCount = computed(() => entitiesData.value.length)

// Charger les entités côté client
const loadEntities = async () => {
  try {
    console.log('🔄 Chargement des entités côté client...')
    loading.value = true
    error.value = false

    const response = await fetch('/api/menu/entities')
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    entitiesData.value = await response.json()
    console.log(`✅ ${entitiesData.value.length} entités chargées avec succès`)

  } catch (err) {
    console.error('❌ Erreur chargement entités:', err)
    error.value = true
  } finally {
    loading.value = false
  }
}

// Menu avec entités dynamiques
const menuItems = computed(() => {
  if (entitiesData.value.length > 0 && !loading.value) {
    console.log('✅ Mise à jour du menu avec les entités')
    return updateMenuWithEntities(entitiesData.value)
  }
  
  return menu
})

const active: MenuType | null = getActiveItem(currentUrl)
const parent: MenuType | null = active?.parentKey
  ? getParentOfActiveItem(active.parentKey)
  : null

// Charger les entités au montage
onMounted(() => {
  loadEntities()
})
</script>