<!-- resources/js/layoutsparam/AuditModuleLayout-FINAL.vue -->
<template>
  <div class="audit-module-layout-final">
    <!-- Header (Menus depuis BD) -->
    <header class="layout-header">
      <AuditHeaderWithMenusFromBD :moduleCode="moduleCode" />
    </header>

    <!-- Main Container -->
    <div class="layout-container">
      <!-- Sidebar (Modules AUDIT uniquement) -->
      <aside class="layout-sidebar">
        <AuditSidebarModules />
      </aside>

      <!-- Main Content -->
      <main class="layout-main">
        <div class="content-wrapper">
          <!-- Breadcrumb -->
          <nav v-if="showBreadcrumb" aria-label="breadcrumb" class="breadcrumb-nav">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><Link href="/dashboard">🏠 Accueil</Link></li>
              <li v-if="title" class="breadcrumb-item fw-bold">{{ title }}</li>
              <li v-for="item in breadcrumbs" :key="item" class="breadcrumb-item">{{ item }}</li>
            </ol>
          </nav>

          <!-- Page Content -->
          <div class="content-body">
            <slot />
          </div>
        </div>

        <!-- Footer -->
        <footer class="layout-footer">
          <div class="footer-content">
            <div class="footer-left">
              <small class="text-muted">© {{ currentYear }} DDM AUDIT. Tous droits réservés.</small>
            </div>
            <div class="footer-right">
              <small class="text-muted">v1.0.0 | Audit Management System</small>
            </div>
          </div>
        </footer>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AuditHeaderWithMenusFromBD from '@/layoutsparam/partials/components/AuditHeaderWithMenusFromBD.vue'
import AuditSidebarModules from '@/layoutsparam/partials/components/AuditSidebarModules.vue'

const props = defineProps<{
  title?: string
  showBreadcrumb?: boolean
  breadcrumbs?: string[]
  moduleCode?: string
}>()

const page = usePage()

// Computed
const currentYear = computed(() => new Date().getFullYear())
const showBreadcrumb = computed(() => props.showBreadcrumb !== false)

/**
 * Détecte le code du module (fallback)
 */
const moduleCode = computed(() => {
  if (props.moduleCode) return props.moduleCode

  // depuis props Inertia
  const fromProps = (page.props as any)?.module?.code ?? (page.props as any)?.moduleCode ?? null
  if (fromProps) return String(fromProps)

  // depuis URL
  const m = window.location.pathname.match(/^\/m\/([^/?#]+)/)
  return m ? decodeURIComponent(m[1]) : 'audit'
})
</script>

<style scoped>
.audit-module-layout-final {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background: #f8f9fa;
}

/* Header */
.layout-header {
  background: white;
  border-bottom: 1px solid #e9ecef;
  flex-shrink: 0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  z-index: 1040;
}

/* Container */
.layout-container {
  display: flex;
  flex: 1;
  overflow: hidden;
}

/* Sidebar */
.layout-sidebar {
  flex-shrink: 0;
  border-right: 1px solid #e9ecef;
}

/* Main Content */
.layout-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.content-wrapper {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

/* Breadcrumb */
.breadcrumb-nav {
  padding: 1rem;
  flex-shrink: 0;
}

.breadcrumb {
  background-color: white;
  border: 1px solid #e9ecef;
  margin-bottom: 0;
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
}

.breadcrumb-item {
  font-size: 0.9rem;
}

.breadcrumb-item a {
  color: #667eea;
  text-decoration: none;
  font-weight: 500;
}

.breadcrumb-item a:hover {
  text-decoration: underline;
}

.breadcrumb-item.active {
  color: #666;
  font-weight: 600;
}

/* Content Body */
.content-body {
  flex: 1;
  padding: 1rem;
  overflow-y: auto;
}

/* Footer */
.layout-footer {
  margin-top: auto;
  flex-shrink: 0;
  background: white;
  border-top: 1px solid #e9ecef;
  padding: 1rem;
}

.footer-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.footer-left,
.footer-right {
  flex: 1;
}

.footer-right {
  text-align: right;
}

/* Scrollbar */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
  background: #ccc;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #999;
}

/* Responsive */
@media (max-width: 1024px) {
  .layout-sidebar {
    width: 220px;
  }
}

@media (max-width: 768px) {
  .layout-container {
    flex-direction: column;
  }

  .layout-sidebar {
    display: none;
    position: fixed;
    left: 0;
    top: 60px;
    width: 100%;
    height: calc(100vh - 60px);
    z-index: 1039;
    border-right: none;
    border-bottom: 1px solid #e9ecef;
  }

  .layout-sidebar.open {
    display: block;
  }

  .content-wrapper {
    padding: 0;
  }

  .breadcrumb-nav {
    padding: 0.75rem;
  }

  .breadcrumb {
    margin-bottom: 0;
  }

  .content-body {
    padding: 0.75rem;
  }

  .footer-content {
    flex-direction: column;
    gap: 0.5rem;
  }

  .footer-right {
    text-align: left;
  }
}

@media (max-width: 480px) {
  .breadcrumb-item {
    font-size: 0.8rem;
  }

  .footer-content {
    font-size: 0.75rem;
  }
}
</style>