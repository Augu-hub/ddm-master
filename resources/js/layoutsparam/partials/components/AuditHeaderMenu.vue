<!-- resources/js/layoutsparam/partials/components/AuditHeaderMenu.vue -->
<template>
  <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom audit-navbar">
    <div class="container-fluid">
      <!-- Logo -->
      <div class="navbar-brand me-3">
        <span class="fw-bold text-danger">🔴 DDM AUDIT</span>
      </div>

      <!-- Toggle Button (Mobile) -->
      <button 
        class="navbar-toggler" 
        type="button" 
        data-bs-toggle="collapse" 
        data-bs-target="#auditNavbarNav" 
        aria-controls="auditNavbarNav" 
        aria-expanded="false" 
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Menu Items -->
      <div class="collapse navbar-collapse" id="auditNavbarNav">
        <ul class="navbar-nav ms-auto">
          <!-- Loading State -->
          <li v-if="loading" class="nav-item">
            <span class="nav-link text-muted">
              <i class="ti ti-loader animate-spin"></i> Chargement menu...
            </span>
          </li>

          <!-- Error State -->
          <li v-else-if="error" class="nav-item">
            <span class="nav-link text-danger">
              <i class="ti ti-alert-triangle"></i> Erreur chargement
            </span>
          </li>

          <!-- Menu Items (from database) -->
          <template v-else v-for="item in menuItems" :key="item.key || item.label">
            
            <!-- Skip titles and dividers -->
            <li v-if="!item.isTitle && !item.isDivider" class="nav-item" :class="{ dropdown: item.children?.length }">
              
              <!-- Simple Item (no children) -->
              <template v-if="!item.children || item.children.length === 0">
                <Link
                  :href="item.url || '#'"
                  class="nav-link"
                  :class="{ active: isMenuItemActive(item) }"
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
                  :class="{ active: isSectionActive(item) }"
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
                    <li v-if="child.isDivider"><hr class="dropdown-divider"></li>

                    <!-- Child Item (simple) -->
                    <li v-else-if="!child.children || child.children.length === 0">
                      <Link
                        :href="child.url || '#'"
                        class="dropdown-item"
                        :class="{ active: isMenuItemActive(child) }"
                      >
                        <span v-if="child.icon" class="me-2">
                          <i :class="child.icon"></i>
                        </span>
                        {{ child.label }}
                      </Link>
                    </li>

                    <!-- Child with nested dropdown -->
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
                            :class="{ active: isMenuItemActive(subChild) }"
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
        </ul>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { MenuType } from '@/types/layout'
import { buildMenuForAudit } from '@/helpers/buildMenuForAudit'

const props = defineProps<{
  moduleCode?: string
}>()

const page = usePage()
const currentUrl = computed(() => page.url)

const menuItems = ref<MenuType[]>([])
const loading = ref(true)
const error = ref(false)

/**
 * Détecte le code du module
 */
function detectModuleCode(): string {
  // 1) depuis props Inertia
  const fromProps = (page.props as any)?.module?.code ?? (page.props as any)?.moduleCode ?? null
  if (fromProps) return String(fromProps)

  // 2) depuis URL /m/{code}/...
  const m = window.location.pathname.match(/^\/m\/([^/?#]+)/)
  if (m) return decodeURIComponent(m[1])

  // 3) par défaut: audit
  return 'audit'
}

/**
 * Charge les menus depuis la base de données
 */
async function loadMenus() {
  try {
    loading.value = true
    error.value = false

    const moduleCode = props.moduleCode || detectModuleCode()
    menuItems.value = await buildMenuForAudit(moduleCode)
  } catch (e) {
    console.error('❌ Erreur chargement menus:', e)
    error.value = true
    menuItems.value = []
  } finally {
    loading.value = false
  }
}

/**
 * Vérifie si un item est actif
 */
const isMenuItemActive = (item: MenuType): boolean => {
  if (!item.url) return false
  if (item.url === currentUrl.value) return true
  return currentUrl.value.startsWith(item.url)
}

/**
 * Vérifie si une section (avec enfants) est active
 */
const isSectionActive = (section: MenuType): boolean => {
  if (!section.children) return false
  return section.children.some((child) => {
    if (child.children?.length) {
      return child.children.some((sc) => isMenuItemActive(sc))
    }
    return isMenuItemActive(child)
  })
}

onMounted(() => {
  loadMenus()
})
</script>

<style scoped>
.audit-navbar {
  padding: 0.75rem 0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.navbar-brand {
  font-size: 1rem;
  font-weight: 700;
  white-space: nowrap;
}

.nav-link {
  color: #333 !important;
  padding: 0.5rem 1rem !important;
  transition: all 0.3s ease;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
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

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Nested dropdown styling */
.dropend .dropdown-menu {
  margin-left: 0;
}

/* Mobile responsive */
@media (max-width: 991px) {
  .navbar-collapse {
    padding-top: 1rem;
    border-top: 1px solid #e0e0e0;
    margin-top: 0.5rem;
  }

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