<!-- resources/js/layoutsparam/partials/components/AuditSidebarModules.vue -->
<template>
  <aside class="audit-sidebar-modules">
    <!-- Header -->
    <div class="sidebar-header">
      <div class="sidebar-brand">
        <div class="brand-icon">
          <i class="ti ti-checklist"></i>
        </div>
        <div class="brand-text">
          <small class="d-block fw-bold">DDM</small>
          <small class="d-block text-muted">AUDIT</small>
        </div>
      </div>
    </div>

    <!-- User Info -->
    <div class="sidebar-user">
      <div class="user-avatar">{{ userInitial }}</div>
      <div class="user-info">
        <small class="d-block fw-bold">{{ userName }}</small>
        <small class="d-block text-muted">{{ userRole }}</small>
      </div>
    </div>

    <!-- Divider -->
    <div class="sidebar-divider"></div>

    <!-- Menu Modules -->
    <nav class="sidebar-menu">
      <!-- Loading -->
      <div v-if="loading" class="menu-loading">
        <i class="ti ti-loader animate-spin"></i>
        <small>Chargement...</small>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="menu-error">
        <i class="ti ti-alert-triangle"></i>
        <small>Erreur: {{ error }}</small>
      </div>

      <!-- Modules List -->
      <ul v-else class="modules-list">
        <!-- Dashboard -->
        <li class="module-item" :class="{ active: isActive('/dashboard/audit') }">
          <Link href="/dashboard/audit" class="module-link">
            <span class="link-icon">📊</span>
            <span class="link-text">Dashboard</span>
          </Link>
        </li>

        <!-- All Other Audit Menus (from BD) -->
        <template v-for="item in auditMenus" :key="item.key">
          <AuditSidebarMenuItem :item="item" :activeUrl="currentUrl" />
        </template>

        <!-- Divider -->
        <li class="module-divider"></li>

        <!-- Settings -->
        <li class="module-item" :class="{ active: isActive('/settings') }">
          <a href="#" class="module-link" @click.prevent="toggleSettings">
            <span class="link-icon">⚙️</span>
            <span class="link-text">Paramètres</span>
            <span class="link-arrow" :class="{ expanded: showSettings }">›</span>
          </a>

          <!-- Settings Submenu -->
          <ul v-if="showSettings" class="submenu">
            <li><Link href="/audit/settings" class="submenu-link">Configuration</Link></li>
            <li><Link href="/audit/users" class="submenu-link">Utilisateurs</Link></li>
            <li><Link href="/audit/permissions" class="submenu-link">Permissions</Link></li>
          </ul>
        </li>

        <!-- Profile -->
        <li class="module-item">
          <Link href="/profile" class="module-link">
            <span class="link-icon">👤</span>
            <span class="link-text">Profil</span>
          </Link>
        </li>

        <!-- Logout -->
        <li class="module-item">
          <form action="/logout" method="POST" class="d-inline w-100">
            <input type="hidden" name="_token" :value="csrfToken">
            <button type="submit" class="module-link logout-btn">
              <span class="link-icon">🚪</span>
              <span class="link-text">Déconnexion</span>
            </button>
          </form>
        </li>
      </ul>
    </nav>

    <!-- Footer -->
    <div class="sidebar-footer">
      <small class="text-muted d-block text-center">v1.0.0</small>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { MenuType } from '@/types/layout'
import { buildMenuForAudit } from '@/helpers/buildMenuForAudit'
import AuditSidebarMenuItem from './AuditSidebarMenuItem.vue'

const page = usePage()

// State
const auditMenus = ref<MenuType[]>([])
const loading = ref(true)
const error = ref<string | false>(false)
const showSettings = ref(false)

// Computed
const currentUrl = computed(() => page.url)
const csrfToken = computed(() => (page.props as any).csrf_token || '')
const user = computed(() => (page.props as any)?.auth?.user)

const userName = computed(() => user.value?.name || 'User')
const userRole = computed(() => 'Audit Manager')
const userInitial = computed(() => (userName.value?.charAt(0) || 'U').toUpperCase())

/**
 * Load AUDIT menus from database
 * 
 * ✅ CORRECT: buildMenuForAudit() sans paramètres
 * ✅ Charge DIRECTEMENT depuis /api/menu/structure?module=audit
 * ✅ Cache localStorage 1h
 */
async function loadMenus() {
  try {
    loading.value = true
    error.value = false
    console.log('📚 Loading AUDIT menus for sidebar...')
    
    // ✅ APPEL CORRECT - sans paramètre 'audit'
    // buildMenuForAudit() charge automatiquement module AUDIT
    const menus = await buildMenuForAudit({ forceRefresh: false })
    
    // Filtrer pour retirer les titles et dividers (garder seulement les items cliquables)
    const filteredMenus = menus.filter(m => !m.isTitle && !m.isDivider)
    
    auditMenus.value = filteredMenus
    
    if (filteredMenus.length > 0) {
      console.log(`✅ ${filteredMenus.length} menus AUDIT chargés`)
    } else {
      console.warn('⚠️ Aucun menu AUDIT chargé (BD vide ou permissions restrictives)')
      error.value = 'Aucun menu disponible'
    }
  } catch (e) {
    console.error('❌ Erreur loading menus:', e)
    error.value = e instanceof Error ? e.message : 'Erreur inconnue'
    auditMenus.value = []
  } finally {
    loading.value = false
  }
}

/**
 * Check if URL is active
 */
const isActive = (url: string): boolean => {
  return currentUrl.value.startsWith(url)
}

/**
 * Toggle settings submenu
 */
const toggleSettings = () => {
  showSettings.value = !showSettings.value
}

onMounted(() => {
  loadMenus()
})
</script>

<style scoped>
.audit-sidebar-modules {
  width: 260px;
  height: 100vh;
  background: linear-gradient(180deg, #f8f9fa 0%, #f3f4f6 100%);
  border-right: 1px solid #e9ecef;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  position: sticky;
  top: 0;
}

/* Header */
.sidebar-header {
  padding: 1rem;
  border-bottom: 1px solid #e9ecef;
  flex-shrink: 0;
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.brand-icon {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
  color: white;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  flex-shrink: 0;
}

.brand-text {
  flex: 1;
}

.brand-text small {
  line-height: 1.2;
  color: #1f2937;
  font-weight: 600;
}

/* User Info */
.sidebar-user {
  padding: 0.75rem 1rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  border-bottom: 1px solid #e9ecef;
  flex-shrink: 0;
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.9rem;
  flex-shrink: 0;
}

.user-info small {
  display: block;
  line-height: 1.2;
}

.user-info small:first-child {
  font-weight: 600;
  color: #1f2937;
}

/* Divider */
.sidebar-divider {
  height: 1px;
  background: #e9ecef;
  flex-shrink: 0;
}

/* Menu */
.sidebar-menu {
  flex: 1;
  overflow-y: auto;
  padding: 0.5rem 0;
}

.menu-loading,
.menu-error {
  padding: 2rem 1rem;
  text-align: center;
  color: #6b7280;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.menu-loading i,
.menu-error i {
  font-size: 1.5rem;
}

.menu-error {
  color: #dc2626;
}

.modules-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

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

/* Divider */
.module-divider {
  height: 1px;
  background: #e9ecef;
  margin: 0.5rem 0;
  list-style: none;
}

/* Submenu */
.submenu {
  list-style: none;
  padding: 0;
  margin: 0;
  background: rgba(0, 0, 0, 0.02);
}

.submenu li {
  display: block;
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

.submenu-link.active {
  background: rgba(102, 126, 234, 0.12);
  color: #667eea;
  font-weight: 600;
}

/* Logout Button */
.logout-btn {
  color: #dc2626;
}

.logout-btn:hover {
  background-color: rgba(220, 38, 38, 0.08);
  color: #b91c1c;
  border-left-color: #dc2626;
}

/* Footer */
.sidebar-footer {
  padding: 0.75rem;
  border-top: 1px solid #e9ecef;
  text-align: center;
  flex-shrink: 0;
}

/* Scrollbar */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

/* Spinner */
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 1024px) {
  .audit-sidebar-modules {
    width: 220px;
  }

  .link-text {
    font-size: 0.85rem;
  }
}

@media (max-width: 768px) {
  .audit-sidebar-modules {
    position: fixed;
    left: -260px;
    z-index: 1045;
    transition: left 0.3s ease;
  }

  .audit-sidebar-modules.open {
    left: 0;
  }
}
</style>