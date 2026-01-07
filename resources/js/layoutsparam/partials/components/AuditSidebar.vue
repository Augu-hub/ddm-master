<!-- resources/js/layoutsparam/partials/components/AuditSidebar.vue -->
<template>
  <div class="audit-sidebar">
    <!-- Logo Section -->
    <div class="sidebar-logo p-3 border-bottom border-light">
      <div class="d-flex align-items-center gap-2">
        <div class="logo-icon">
          <span class="fs-5 fw-bold">🔴</span>
        </div>
        <div class="logo-text">
          <small class="d-block fw-bold">DDM</small>
          <small class="d-block text-light" style="font-size: 0.7rem;">AUDIT</small>
        </div>
      </div>
    </div>

    <!-- User Section -->
    <div class="sidebar-user p-3 border-bottom border-light text-light">
      <div class="d-flex align-items-center gap-2">
        <div class="user-avatar" style="width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.3); display: flex; align-items: center; justify-content: center;">
          👤
        </div>
        <div class="user-info">
          <small class="d-block fw-bold">{{ userName }}</small>
          <small class="d-block text-light" style="font-size: 0.7rem;">{{ userRole }}</small>
        </div>
      </div>
    </div>

    <!-- Menu -->
    <nav class="sidebar-menu">
      <ul class="menu-list">
        <!-- Dashboard -->
        <li class="menu-item" :class="{ active: isActive('/dashboard/audit') }">
          <Link href="/dashboard/audit" class="menu-link">
            <span class="menu-icon">📊</span>
            <span class="menu-text">Dashboard</span>
          </Link>
        </li>

        <!-- Risques (Expanded by default) -->
        <li class="menu-item" :class="{ active: isActive('/dashboard/risk') }">
          <a class="menu-link" @click="toggleMenu('risques')" role="button">
            <span class="menu-icon">🔴</span>
            <span class="menu-text">Risques</span>
            <span class="menu-arrow" :class="{ expanded: expandedMenus.risques }">›</span>
          </a>
          <ul v-if="expandedMenus.risques" class="submenu">
            <li><Link href="/dashboards/risk" class="submenu-link">Identification</Link></li>
            <li><Link href="/dashboards/risk#matrice" class="submenu-link">Matrice</Link></li>
            <li><Link href="/dashboards/risk#frequences" class="submenu-link">Fréquences</Link></li>
            <li><Link href="/dashboards/risk#impacts" class="submenu-link">Impacts</Link></li>
          </ul>
        </li>

        <!-- Contrôles -->
        <li class="menu-item">
          <a class="menu-link" @click="toggleMenu('controles')" role="button">
            <span class="menu-icon">✅</span>
            <span class="menu-text">Contrôles</span>
            <span class="menu-arrow" :class="{ expanded: expandedMenus.controles }">›</span>
          </a>
          <ul v-if="expandedMenus.controles" class="submenu">
            <li><Link href="/dashboard/controls" class="submenu-link">Liste</Link></li>
            <li><Link href="/dashboard/controls/matrix" class="submenu-link">Matrice</Link></li>
          </ul>
        </li>

        <!-- Rapports -->
        <li class="menu-item">
          <a class="menu-link" @click="toggleMenu('rapports')" role="button">
            <span class="menu-icon">📋</span>
            <span class="menu-text">Rapports</span>
            <span class="menu-arrow" :class="{ expanded: expandedMenus.rapports }">›</span>
          </a>
          <ul v-if="expandedMenus.rapports" class="submenu">
            <li><Link href="/dashboard/reports/risk" class="submenu-link">Risques</Link></li>
            <li><Link href="/dashboard/reports/controls" class="submenu-link">Contrôles</Link></li>
            <li><Link href="/dashboard/reports/executive" class="submenu-link">Exécutif</Link></li>
          </ul>
        </li>

        <!-- Divider -->
        <li class="menu-divider my-2"></li>

        <!-- Paramètres -->
        <li class="menu-item">
          <a class="menu-link" @click="toggleMenu('param')" role="button">
            <span class="menu-icon">⚙️</span>
            <span class="menu-text">Paramètres</span>
            <span class="menu-arrow" :class="{ expanded: expandedMenus.param }">›</span>
          </a>
          <ul v-if="expandedMenus.param" class="submenu">
            <li><Link href="/param/projects" class="submenu-link">Projets</Link></li>
            <li><Link href="/param/entities" class="submenu-link">Entités</Link></li>
            <li><Link href="/param/users" class="submenu-link">Utilisateurs</Link></li>
          </ul>
        </li>

        <!-- Divider -->
        <li class="menu-divider my-2"></li>

        <!-- Profil -->
        <li class="menu-item">
          <Link href="/profile" class="menu-link">
            <span class="menu-icon">👤</span>
            <span class="menu-text">Profil</span>
          </Link>
        </li>

        <!-- Déconnexion -->
        <li class="menu-item">
          <Link href="/logout" method="post" as="button" class="menu-link">
            <span class="menu-icon">🚪</span>
            <span class="menu-text">Déconnexion</span>
          </Link>
        </li>
      </ul>
    </nav>

    <!-- Footer Info -->
    <div class="sidebar-footer p-3 border-top border-light text-light mt-auto">
      <small class="d-block text-center">DDM AUDIT</small>
      <small class="d-block text-center text-light" style="font-size: 0.7rem;">v1.0.0</small>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const currentUrl = computed(() => page.url)

const expandedMenus = ref({
  risques: true,
  controles: false,
  rapports: false,
  param: false,
})

const userName = 'Augustin D.'
const userRole = 'Audit Manager'

const toggleMenu = (menu: string) => {
  expandedMenus.value[menu] = !expandedMenus.value[menu]
}

const isActive = (url: string) => {
  return currentUrl.value.startsWith(url)
}
</script>

<style scoped>
.audit-sidebar {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.logo-icon {
  font-size: 1.5rem;
}

.logo-text small {
  line-height: 1.2;
}

.sidebar-user {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-info {
  overflow: hidden;
}

.sidebar-menu {
  flex: 1;
  overflow-y: auto;
  padding: 0.5rem 0;
}

.menu-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.menu-item {
  position: relative;
}

.menu-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  transition: all 0.3s ease;
  cursor: pointer;
  border-left: 3px solid transparent;
}

.menu-link:hover {
  background: rgba(255, 255, 255, 0.1);
  color: white;
}

.menu-item.active > .menu-link {
  background: rgba(255, 255, 255, 0.15);
  color: white;
  border-left-color: #ffd700;
  font-weight: 600;
}

.menu-icon {
  font-size: 1.1rem;
  flex-shrink: 0;
}

.menu-text {
  flex: 1;
  font-size: 0.9rem;
}

.menu-arrow {
  transform: rotate(0deg);
  transition: transform 0.3s ease;
  font-size: 1.2rem;
}

.menu-arrow.expanded {
  transform: rotate(90deg);
}

.menu-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.2);
  margin: 0.5rem 0;
}

.submenu {
  list-style: none;
  padding: 0;
  margin: 0;
  background: rgba(0, 0, 0, 0.1);
}

.submenu li {
  display: block;
}

.submenu-link {
  display: block;
  padding: 0.5rem 1rem 0.5rem 2.75rem;
  color: rgba(255, 255, 255, 0.7);
  text-decoration: none;
  font-size: 0.85rem;
  transition: all 0.2s ease;
}

.submenu-link:hover {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  padding-left: 3rem;
}

.sidebar-footer {
  margin-top: auto;
  text-align: center;
  font-size: 0.75rem;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
}

/* Scrollbar */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.1);
}

::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.5);
}

/* Responsive */
@media (max-width: 768px) {
  .sidebar-logo,
  .sidebar-user {
    padding: 0.75rem;
  }

  .menu-link {
    padding: 0.6rem 0.75rem;
  }

  .menu-text {
    font-size: 0.8rem;
  }

  .submenu-link {
    padding-left: 2.25rem;
  }

  .submenu-link:hover {
    padding-left: 2.5rem;
  }
}
</style>