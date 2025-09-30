<!-- layoutsparam/partials/components/VerticalMenu.vue - VERSION DEBUG -->
<template>
  <ul id="navbar-nav" class="navbar-nav">
    <!-- DEBUG -->
    <li class="nav-item">
      <a class="nav-link menu-link text-danger">
        <i class="ti ti-bug"></i>
        <span>DEBUG: {{ entitiesCount }} entités</span>
      </a>
    </li>
    
    <template v-for="item in menuItems" :key="item.key">
      <!-- Menu Title -->
      <li class="menu-title" v-if="item.isTitle">
        <span data-key="t-menu">{{ item.label }}</span>
      </li>
      
      <!-- Menu Item -->
      <li class="nav-item" v-else>
        <!-- Simple Link (sans enfants) -->
        <a 
          v-if="!item.children || item.children.length === 0"
          :href="item.url" 
          class="nav-link menu-link"
          :class="{ active: isActive(item.url) }"
        >
          <i :class="item.icon"></i>
          <span data-key="t-menu">{{ item.label }}</span>
        </a>
        
        <!-- Menu avec enfants -->
        <a 
          v-else
          class="nav-link menu-link"
          :class="{ active: isParentActive(item.children) }"
          data-bs-toggle="collapse"
          :href="`#menu-${item.key}`"
        >
          <i :class="item.icon"></i>
          <span data-key="t-menu">{{ item.label }}</span>
          <span class="menu-arrow"></span>
        </a>
        
        <!-- Sous-menu -->
        <div 
          v-if="item.children && item.children.length > 0"
          :id="`menu-${item.key}`"
          class="collapse menu-dropdown"
          :class="{ show: isParentActive(item.children) }"
        >
          <ul class="nav nav-sm flex-column">
            <li class="nav-item" v-for="child in item.children" :key="child.key">
              <a 
                :href="child.url" 
                class="nav-link"
                :class="{ active: isActive(child.url) }"
              >
                <i :class="child.icon"></i>
                {{ child.label }}
              </a>
            </li>
          </ul>
        </div>
      </li>
    </template>
  </ul>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { menu, updateMenuWithEntities } from '@/helpers/menu'

const page = usePage()

// Debug forcé
console.log('=== 🚨 DEBUG VERTICAL MENU 🚨 ===')
console.log('page.props:', page.props)
console.log('page.props.entities:', (page.props as any).entities)

const entitiesData = computed(() => {
  const entities = (page.props as any).entities || []
  console.log('Entities dans computed:', entities)
  return entities
})

const entitiesCount = computed(() => entitiesData.value.length)

const menuItems = computed(() => {
  console.log('Création du menu avec', entitiesCount.value, 'entités')
  
  if (entitiesCount.value > 0) {
    const updatedMenu = updateMenuWithEntities(entitiesData.value)
    console.log('Menu mis à jour:', updatedMenu)
    
    // Vérifiez spécifiquement les organigrammes entité
    const paramItem = updatedMenu.find((item: any) => item.key === 'param')
    if (paramItem) {
      const chartsEntity = paramItem.children.find((child: any) => child.key === 'charts_entity')
      console.log('Organigrammes Entité:', chartsEntity)
    }
    
    return updatedMenu
  }
  
  return menu
})

const isActive = (url?: string) => {
  if (!url) return false
  return page.url.startsWith(url)
}

const isParentActive = (children: any[]) => {
  return children && children.some((child: any) => isActive(child.url))
}
</script>