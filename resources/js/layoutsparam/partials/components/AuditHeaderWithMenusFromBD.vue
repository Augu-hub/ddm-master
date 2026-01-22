<!-- resources/js/layoutsparam/partials/components/AuditHeaderWithMenusFromBD.vue -->
<template>
  <nav class="navbar navbar-expand-lg navbar-light bg-white audit-navbar-bd">
    <div class="container-fluid">
      <!-- Logo AUDIT -->
      <div class="navbar-brand me-3">
        <span class="fw-bold text-danger d-flex align-items-center gap-2">
          <i class="ti ti-checklist"></i>
          <span>AUDIT</span>
        </span>
      </div>

      <!-- Toggle Button (Mobile) -->
      <button 
        class="navbar-toggler" 
        type="button" 
        data-bs-toggle="collapse" 
        data-bs-target="#auditNavbarFromBD" 
        aria-controls="auditNavbarFromBD" 
        aria-expanded="false" 
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Menu Items FROM DATABASE -->
      <div class="collapse navbar-collapse" id="auditNavbarFromBD">
        <ul class="navbar-nav ms-auto">
          <!-- Loading -->
          <li v-if="loading" class="nav-item">
            <span class="nav-link text-muted">
              <i class="ti ti-loader animate-spin"></i> Menu...
            </span>
          </li>

          <!-- Error -->
          <li v-else-if="error" class="nav-item">
            <span class="nav-link text-danger" title="Erreur - utilisation fallback">
              <i class="ti ti-alert-triangle"></i> Erreur
              <button class="btn btn-sm btn-link ms-2 p-0" @click="retryLoad" title="Réessayer">🔄</button>
            </span>
          </li>

          <!-- Menu Items (Récursif) -->
          <template v-else v-for="item in menuItems" :key="item.key">
            <AuditMenuItemBD :item="item" :activeUrl="currentUrl" />
          </template>

          <!-- Debug Button (Dev Only) -->
          <li v-if="isDev" class="nav-item ms-2">
            <button 
              class="nav-link btn btn-sm btn-link p-1" 
              @click="showDebug = !showDebug"
              title="Debug"
            >
              🐛
            </button>
          </li>
        </ul>
      </div>
    </div>

    <!-- Debug Panel (Dev Only) -->
    <div v-if="isDev && showDebug" class="audit-navbar-debug">
      <div class="debug-header">
        <span>🐛 DEBUG AUDIT Header Menu</span>
        <button class="btn-close btn-close-white" @click="showDebug = false"></button>
      </div>
      <div class="debug-content">
        <p class="mb-1"><strong>Status:</strong> {{ loading ? 'Loading' : error ? 'Error' : 'OK ✅' }}</p>
        <p class="mb-1"><strong>Items:</strong> {{ menuItems.length }}</p>
        <p class="mb-2"><strong>Module:</strong> {{ moduleCode }}</p>
        <button @click="refreshMenus" class="btn btn-sm btn-primary me-2">🔄 Refresh</button>
        <button @click="clearCache" class="btn btn-sm btn-danger">🗑️ Clear</button>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { MenuType } from '@/types/layout'
import { buildMenuForAudit } from '@/helpers/buildMenuForAudit'
import AuditMenuItemBD from './AuditMenuItemBD.vue'

const props = defineProps<{
  moduleCode?: string
}>()

const page = usePage()

// State
const menuItems = ref<MenuType[]>([])
const loading = ref(true)
const error = ref(false)
const showDebug = ref(false)

// Computed
const currentUrl = computed(() => page.url)
const isDev = computed(() => import.meta.env.DEV)
const moduleCode = computed(() => {
  if (props.moduleCode) return props.moduleCode
  const fromProps = (page.props as any)?.module?.code ?? (page.props as any)?.moduleCode ?? null
  if (fromProps) return String(fromProps)
  const m = window.location.pathname.match(/^\/m\/([^/?#]+)/)
  return m ? decodeURIComponent(m[1]) : 'audit'
})

/**
 * Load menus from database
 */
async function loadMenus(forceRefresh = false) {
  try {
    loading.value = true
    error.value = false
    console.log(`📚 Loading AUDIT menus for: ${moduleCode.value}`)
    menuItems.value = await buildMenuForAudit(moduleCode.value, forceRefresh)
    if (!menuItems.value.length) {
      error.value = true
      console.warn('⚠️ Aucun menu reçu')
    }
  } catch (e) {
    console.error('❌ Erreur loading menus:', e)
    error.value = true
  } finally {
    loading.value = false
  }
}

/**
 * Actions
 */
async function refreshMenus() {
  console.log('🔄 Refreshing menus from BD...')
  await loadMenus(true)
}

async function clearCache() {
  console.log('🧹 Clearing cache...')
  const { invalidateAuditMenuCache } = await import('@/helpers/buildMenuForAudit')
  invalidateAuditMenuCache()
  await loadMenus(true)
}

async function retryLoad() {
  console.log('♻️ Retrying...')
  await loadMenus(false)
}

onMounted(() => {
  loadMenus(false)
})

// Expose
defineExpose({
  refreshMenus,
  clearCache,
})
</script>

<style scoped>
.audit-navbar-bd {
  padding: 0.75rem 0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  background: white !important;
  border-bottom: 1px solid #e9ecef;
}

.navbar-brand {
  font-size: 1rem;
  font-weight: 700;
  white-space: nowrap;
  color: #dc2626 !important;
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

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Debug Panel */
.audit-navbar-debug {
  position: fixed;
  bottom: 20px;
  right: 20px;
  background: #1f2937;
  color: white;
  border: 2px solid #667eea;
  border-radius: 0.5rem;
  padding: 1rem;
  max-width: 300px;
  z-index: 9999;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
}

.debug-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
  font-weight: 600;
  font-size: 0.9rem;
}

.debug-content {
  font-size: 0.85rem;
  line-height: 1.6;
}

/* Mobile */
@media (max-width: 991px) {
  .navbar-collapse {
    padding-top: 1rem;
    border-top: 1px solid #e0e0e0;
    margin-top: 0.5rem;
  }

  .nav-link {
    padding: 0.75rem 1rem !important;
  }

  .audit-navbar-debug {
    max-width: 90vw;
  }
}
</style>