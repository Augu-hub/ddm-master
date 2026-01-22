<!-- resources/js/layoutsparam/partials/components/AuditSidebarMenuItem.vue -->
<template>
  <li v-if="!item.isTitle && !item.isDivider" class="module-item" :class="{ active: isActive(item) }">
    
    <!-- Simple Item (no children) -->
    <template v-if="!item.children || item.children.length === 0">
      <Link
        :href="item.url || '#'"
        class="module-link"
      >
        <span class="link-icon" v-if="item.icon">
          <i :class="item.icon"></i>
        </span>
        <span v-else class="link-icon">📄</span>
        <span class="link-text">{{ item.label }}</span>
      </Link>
    </template>

    <!-- Item with children (expandable) -->
    <template v-else>
      <a 
        @click.prevent="toggleExpanded"
        class="module-link"
        role="button"
      >
        <span class="link-icon" v-if="item.icon">
          <i :class="item.icon"></i>
        </span>
        <span v-else class="link-icon">📁</span>
        <span class="link-text">{{ item.label }}</span>
        <span class="link-arrow" :class="{ expanded: isExpanded }">›</span>
      </a>

      <!-- Submenu -->
      <ul v-if="isExpanded" class="submenu">
        <template v-for="child in item.children" :key="child.key">
          
          <!-- Divider -->
          <li v-if="child.isDivider" class="submenu-divider"></li>

          <!-- Child Item -->
          <li v-else-if="!child.children || child.children.length === 0" class="submenu-item" :class="{ active: isActive(child) }">
            <Link
              :href="child.url || '#'"
              class="submenu-link"
            >
              <span class="submenu-icon" v-if="child.icon">
                <i :class="child.icon"></i>
              </span>
              {{ child.label }}
            </Link>
          </li>

          <!-- Nested Child -->
          <li v-else class="submenu-item submenu-nested">
            <AuditSidebarMenuItem :item="child" :activeUrl="activeUrl" />
          </li>
        </template>
      </ul>
    </template>
  </li>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { MenuType } from '@/types/layout'

const props = defineProps<{
  item: MenuType
  activeUrl?: string
}>()

const page = usePage()
const isExpanded = ref(false)

const currentUrl = computed(() => props.activeUrl || page.url)

const isActive = (menu: MenuType): boolean => {
  if (!menu.url) return false
  if (menu.url === currentUrl.value) return true
  return currentUrl.value.startsWith(menu.url)
}

const toggleExpanded = () => {
  isExpanded.value = !isExpanded.value
}
</script>

<style scoped>
.module-item {
  position: relative;
}

.module-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  color: #4b5563;
  text-decoration: none;
  transition: all 0.2s ease;
  font-size: 0.9rem;
  border-left: 3px solid transparent;
  cursor: pointer;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
}

.module-link:hover {
  background-color: rgba(102, 126, 234, 0.08);
  color: #667eea;
  border-left-color: #667eea;
}

.module-item.active > .module-link {
  background-color: rgba(102, 126, 234, 0.12);
  color: #667eea;
  font-weight: 600;
  border-left-color: #667eea;
}

.link-icon {
  font-size: 1.1rem;
  flex-shrink: 0;
  min-width: 24px;
}

.link-text {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.link-arrow {
  font-size: 1.2rem;
  transition: transform 0.2s ease;
  transform: rotate(0deg);
}

.link-arrow.expanded {
  transform: rotate(90deg);
}

/* Submenu */
.submenu {
  list-style: none;
  padding: 0;
  margin: 0;
  background: rgba(0, 0, 0, 0.02);
}

.submenu-divider {
  height: 1px;
  background: #e9ecef;
  margin: 0.25rem 0;
  list-style: none;
}

.submenu-item {
  display: block;
  list-style: none;
}

.submenu-link {
  display: block;
  padding: 0.5rem 1rem 0.5rem 2.5rem;
  color: #6b7280;
  text-decoration: none;
  font-size: 0.85rem;
  transition: all 0.2s ease;
}

.submenu-link:hover {
  background: rgba(102, 126, 234, 0.08);
  color: #667eea;
}

.submenu-item.active > .submenu-link {
  background: rgba(102, 126, 234, 0.12);
  color: #667eea;
  font-weight: 600;
}

.submenu-icon {
  margin-right: 0.5rem;
  font-size: 0.9rem;
}

/* Nested submenu */
.submenu-nested {
  padding: 0;
  border-left: 2px solid #e9ecef;
}

.submenu-nested .module-link {
  padding-left: 1.75rem;
  font-size: 0.85rem;
}

.submenu-nested .submenu {
  background: transparent;
}
</style>