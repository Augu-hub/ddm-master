<!-- resources/js/layoutsparam/partials/components/AuditMenuItemBD.vue -->
<template>
  <li v-if="!item.isTitle && !item.isDivider" class="nav-item" :class="{ dropdown: item.children?.length }">
    
    <!-- Simple Item (no children) -->
    <template v-if="!item.children || item.children.length === 0">
      <Link
        :href="item.url || '#'"
        class="nav-link"
        :class="{ active: isActive(item) }"
      >
        <span v-if="item.icon" class="me-2">
          <i :class="item.icon"></i>
        </span>
        {{ item.label }}
      </Link>
    </template>

    <!-- Dropdown (with children) -->
    <template v-else>
      <a 
        class="nav-link dropdown-toggle" 
        :href="`#dropdown-${item.key}`"
        role="button" 
        data-bs-toggle="dropdown" 
        aria-expanded="false"
        :class="{ active: isActive(item) }"
      >
        <span v-if="item.icon" class="me-2">
          <i :class="item.icon"></i>
        </span>
        {{ item.label }}
      </a>

      <!-- Dropdown Menu -->
      <ul :id="`dropdown-${item.key}`" class="dropdown-menu">
        <template v-for="child in item.children" :key="child.key">
          
          <!-- Divider -->
          <li v-if="child.isDivider">
            <hr class="dropdown-divider">
          </li>

          <!-- Child Item -->
          <li v-else-if="!child.children || child.children.length === 0">
            <Link
              :href="child.url || '#'"
              class="dropdown-item"
              :class="{ active: isActive(child) }"
            >
              <span v-if="child.icon" class="me-2">
                <i :class="child.icon"></i>
              </span>
              {{ child.label }}
            </Link>
          </li>

          <!-- Nested Dropdown -->
          <li v-else class="dropend">
            <a 
              :href="`#dropdown-${child.key}`"
              class="dropdown-item dropdown-toggle"
              role="button"
              data-bs-toggle="dropdown"
            >
              <span v-if="child.icon" class="me-2">
                <i :class="child.icon"></i>
              </span>
              {{ child.label }}
            </a>
            <ul :id="`dropdown-${child.key}`" class="dropdown-menu">
              <li v-for="subChild in child.children" :key="subChild.key">
                <Link
                  :href="subChild.url || '#'"
                  class="dropdown-item"
                  :class="{ active: isActive(subChild) }"
                >
                  {{ subChild.label }}
                </Link>
              </li>
            </ul>
          </li>
        </template>
      </ul>
    </template>
  </li>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { MenuType } from '@/types/layout'

const props = defineProps<{
  item: MenuType
  activeUrl?: string
}>()

const page = usePage()
const currentUrl = computed(() => props.activeUrl || page.url)

const isActive = (menu: MenuType): boolean => {
  if (!menu.url) return false
  if (menu.url === currentUrl.value) return true
  return currentUrl.value.startsWith(menu.url)
}
</script>

<style scoped>
.nav-item {
  position: relative;
}

.nav-link {
  color: #333 !important;
  padding: 0.5rem 1rem !important;
  transition: all 0.3s ease;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  cursor: pointer;
}

.nav-link:hover {
  color: #667eea !important;
  background-color: rgba(102, 126, 234, 0.1);
  border-radius: 0.25rem;
}

.nav-link.active {
  color: #667eea !important;
  font-weight: 600;
  border-bottom: 3px solid #667eea;
  padding-bottom: calc(0.5rem - 3px) !important;
}

.dropdown-menu {
  border-radius: 0.25rem;
  border: 1px solid #e0e0e0;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  min-width: 220px;
}

.dropdown-item {
  padding: 0.6rem 1rem;
  font-size: 0.9rem;
  color: #333;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
}

.dropdown-item:hover {
  background-color: #f8f9fa;
  color: #667eea;
}

.dropdown-item.active {
  background-color: rgba(102, 126, 234, 0.15);
  color: #667eea;
  font-weight: 600;
}

.dropdown-divider {
  margin: 0.5rem 0;
  border-color: #e0e0e0;
}

@media (max-width: 991px) {
  .nav-link {
    padding: 0.75rem 1rem !important;
  }

  .dropdown-menu {
    border: none;
    box-shadow: none;
    background-color: #f8f9fa;
    margin-top: 0.5rem;
  }
}
</style>