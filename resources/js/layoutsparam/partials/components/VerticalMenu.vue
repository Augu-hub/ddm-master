<!-- layoutsparam/partials/components/VerticalMenu.vue -->
<template>
  <ul class="side-nav">
    <!-- Menu normal -->
    <template v-for="section in menuItems" :key="section.key || section.label">
      <!-- Titre de section -->
      <li v-if="section.isTitle" class="side-nav-title">
        {{ section.label }}
      </li>

      <!-- Séparateur -->
      <li v-else-if="section.isDivider" class="side-nav-divider"></li>

      <!-- Élément de menu avec enfants -->
      <li v-else-if="section.children && section.children.length > 0" 
          class="side-nav-item" 
          :class="{ active: isSectionActive(section) }">
        
        <a class="side-nav-link" v-b-toggle="'collapse-' + section.key" role="button">
          <span class="menu-icon" v-if="section.icon">
            <i :class="section.icon"></i>
          </span>
          <span class="menu-text">{{ section.label }}</span>
          <span class="menu-arrow"></span>
        </a>

        <b-collapse :id="'collapse-' + section.key" :visible="isSectionActive(section)">
          <ul class="sub-menu">
            <li v-for="child in section.children" 
                :key="child.key" 
                class="side-nav-item" 
                :class="{ active: isMenuItemActive(child) }">
              
              <!-- Sous-élément avec enfants -->
              <template v-if="child.children && child.children.length > 0">
                <a class="side-nav-link" v-b-toggle="'collapse-' + child.key" role="button">
                  <span class="menu-text">{{ child.label }}</span>
                  <span class="menu-arrow"></span>
                </a>
                
                <b-collapse :id="'collapse-' + child.key" :visible="isMenuItemActive(child)">
                  <ul class="sub-menu">
                    <li v-for="subChild in child.children" 
                        :key="subChild.key" 
                        class="side-nav-item" 
                        :class="{ active: isMenuItemActive(subChild) }">
                      
                      <Link :href="subChild.url!" 
                            :target="subChild.target" 
                            class="side-nav-link" 
                            :class="{ active: isMenuItemActive(subChild) }">
                        <span class="menu-text">{{ subChild.label }}</span>
                      </Link>
                    </li>
                  </ul>
                </b-collapse>
              </template>

              <!-- Sous-élément sans enfants -->
              <template v-else>
                <Link :href="child.url!" 
                      :target="child.target" 
                      class="side-nav-link" 
                      :class="{ active: isMenuItemActive(child) }">
                  <span class="menu-text">{{ child.label }}</span>
                </Link>
              </template>
            </li>
          </ul>
        </b-collapse>
      </li>

      <!-- Élément de menu sans enfants -->
      <li v-else class="side-nav-item" :class="{ active: isMenuItemActive(section) }">
        <Link :href="section.url!" 
              :target="section.target" 
              class="side-nav-link" 
              :class="{ active: isMenuItemActive(section) }">
          <span class="menu-icon" v-if="section.icon">
            <i :class="section.icon"></i>
          </span>
          <span class="menu-text">{{ section.label }}</span>
          
          <!-- Badge -->
          <span v-if="section.badge" 
                class="badge" 
                :class="`badge-${section.badge.variant || 'primary'}`">
            {{ section.badge.text }}
          </span>
        </Link>
      </li>
    </template>

    <!-- État de chargement des entités -->
    <li v-if="loadingEntities" class="side-nav-item">
      <a class="side-nav-link text-info">
        <span class="menu-icon"><i class="ti ti-loader animate-spin"></i></span>
        <span class="menu-text">Chargement des entités...</span>
      </a>
    </li>

    <!-- État d'erreur des entités -->
    <li v-else-if="entitiesError" class="side-nav-item">
      <a class="side-nav-link text-danger">
        <span class="menu-icon"><i class="ti ti-alert-triangle"></i></span>
        <span class="menu-text">Erreur chargement entités</span>
      </a>
    </li>
  </ul>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { buildMenuWithEntities, type MenuType } from '@/helpers/menu'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const currentUrl = page.url

// États pour le menu
const menuItems = ref<MenuType[]>([])
const loadingMenu = ref(true)
const menuError = ref(false)

// États pour les entités
const entitiesData = ref<any[]>([])
const loadingEntities = ref(false)
const entitiesError = ref(false)

// Charger le menu principal
const loadMenu = async () => {
  try {
    console.log('🔄 Chargement du menu principal...')
    loadingMenu.value = true
    menuError.value = false

    // Utiliser la nouvelle fonction qui charge depuis la base
    menuItems.value = await buildMenuWithEntities(entitiesData.value)
    
    console.log('✅ Menu principal chargé avec succès')

  } catch (err) {
    console.error('❌ Erreur chargement menu:', err)
    menuError.value = true
  } finally {
    loadingMenu.value = false
  }
}

// Charger les entités
const loadEntities = async () => {
  try {
    console.log('🔄 Chargement des entités...')
    loadingEntities.value = true
    entitiesError.value = false

    const response = await fetch('/api/menu/entities', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const data = await response.json()
    entitiesData.value = data.entities || data || []
    
    console.log(`✅ ${entitiesData.value.length} entités chargées`)

  } catch (err) {
    console.error('❌ Erreur chargement entités:', err)
    entitiesError.value = true
    entitiesData.value = []
  } finally {
    loadingEntities.value = false
  }
}

// Recharger le menu quand les entités changent
watch(entitiesData, (newEntities) => {
  if (newEntities.length > 0) {
    console.log('🔄 Mise à jour du menu avec les entités...')
    loadMenu()
  }
}, { deep: true })

// Vérifier si un élément de menu est actif
const isMenuItemActive = (menuItem: MenuType): boolean => {
  if (!menuItem.url) return false
  
  // Vérifier l'URL exacte
  if (menuItem.url === currentUrl) return true
  
  // Vérifier les routes associées
  if (menuItem.routes && menuItem.routes.length > 0) {
    const currentRoute = page.component || ''
    return menuItem.routes.includes(currentRoute)
  }
  
  return false
}

// Vérifier si une section est active (a un enfant actif)
const isSectionActive = (section: MenuType): boolean => {
  if (!section.children) return false
  
  return section.children.some(child => {
    if (child.children) {
      return child.children.some(subChild => isMenuItemActive(subChild))
    }
    return isMenuItemActive(child)
  })
}

// Chargement initial
onMounted(async () => {
  // Charger d'abord les entités, puis le menu
  await loadEntities()
  await loadMenu()
})
</script>

<style scoped>
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.side-nav-link.active {
  background-color: rgba(var(--bs-primary-rgb), 0.1);
  color: var(--bs-primary) !important;
  font-weight: 600;
}

.badge {
  margin-left: auto;
  font-size: 0.7em;
}
</style>