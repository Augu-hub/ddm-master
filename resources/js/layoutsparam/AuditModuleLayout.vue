<!-- resources/js/layoutsparam/AuditModuleLayout.vue -->
<template>
  <div class="app-layout audit-module-layout">
    <!-- Header avec Menu Horizontal (chargé depuis BD) -->
    <header class="header-audit-module">
      <AuditHeaderMenu :moduleCode="moduleCode" />
    </header>

    <!-- Main Content -->
    <main class="main-content-audit-module">
      <div class="container-fluid py-4">
        <!-- Breadcrumb -->
        <nav v-if="showBreadcrumb" aria-label="breadcrumb" class="mb-3">
          <ol class="breadcrumb bg-light p-2 rounded">
            <li class="breadcrumb-item"><Link href="/dashboard">🏠 Accueil</Link></li>
            <li v-if="title" class="breadcrumb-item fw-bold">{{ title }}</li>
            <li v-for="item in breadcrumbs" :key="item" class="breadcrumb-item">{{ item }}</li>
          </ol>
        </nav>

        <!-- Page Content -->
        <slot />
      </div>
    </main>

    <!-- Footer -->
    <footer class="footer-audit-module bg-light border-top py-3">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-md-6">
            <small class="text-muted">© 2026 DDM AUDIT. Tous droits réservés.</small>
          </div>
          <div class="col-md-6 text-end">
            <small class="text-muted">v1.0.0 | {{ currentYear }}</small>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AuditHeaderMenu from '@/layoutsparam/partials/components/AuditHeaderMenu.vue'

const props = defineProps<{
  title?: string
  showBreadcrumb?: boolean
  breadcrumbs?: string[]
  moduleCode?: string
}>()

const page = usePage()
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
.app-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background-color: #f8f9fa;
}

.audit-module-layout {
  flex-direction: column;
}

.header-audit-module {
  background: white;
  border-bottom: 1px solid #e9ecef;
  flex-shrink: 0;
}

.main-content-audit-module {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
}

.container-fluid {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  padding: 2rem !important;
}

.footer-audit-module {
  margin-top: auto;
  flex-shrink: 0;
}

.breadcrumb {
  background-color: #f8f9fa;
  border: 1px solid #e9ecef;
  margin-bottom: 1.5rem;
  border-radius: 0.5rem;
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

/* Scrollbar */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
  background: #667eea;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #764ba2;
}

/* Responsive */
@media (max-width: 768px) {
  .container-fluid {
    padding: 1rem !important;
  }

  .breadcrumb {
    font-size: 0.85rem;
  }
}
</style>