<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'

// Importer tous les layouts disponibles
import ParamLayout from '@/pages/dashboards/Param/Entities/create.vue'
// import AuditLayout from '@/layouts/AuditLayout.vue'
// import RiskLayout from '@/layouts/RiskLayout.vue'
// import ProcessLayout from '@/layouts/ProcessLayout.vue'
import ModuleLayout from '@/layoutsparam/VerticalLayout.vue'

// Props
const props = defineProps<{
  module: {
    id: number
    code: string
    name: string
    icon?: string
    service?: {
      id: number
      name: string
    }
  }
  layout?: string
}>()

// Page context
const page = usePage()

// Mapping des layouts
const layoutComponents: Record<string, any> = {
  'Layouts/ParamLayout': ParamLayout,
  // 'Layouts/AuditLayout': AuditLayout,
  // 'Layouts/RiskLayout': RiskLayout,
  // 'Layouts/ProcessLayout': ProcessLayout,
  //'Layouts/ModuleLayout': ModuleLayout,
}

// Déterminer le layout à utiliser
const currentLayout = computed(() => {
  const layoutName = props.layout || 'Layouts/ModuleLayout'
  console.log('🎨 Layout demandé:', layoutName)
  return layoutComponents[layoutName] || ModuleLayout
})

// Couleurs par module
const moduleColor = computed(() => {
  const colors: Record<string, string> = {
    'param.projects': 'linear-gradient(135deg, #3b82f6, #1d4ed8)',
    'audit.core': 'linear-gradient(135deg, #f59e0b, #d97706)',
    'risk.core': 'linear-gradient(135deg, #10b981, #059669)',
    'process.core': 'linear-gradient(135deg, #8b5cf6, #7c3aed)',
  }
  return colors[props.module.code] || 'linear-gradient(135deg, #06b6d4, #0284c7)'
})

onMounted(() => {
  console.log('🚀 ModuleShell monté')
  console.log('📦 Module:', props.module)
  console.log('🎨 Layout:', props.layout)
  console.log('✅ Layout component:', currentLayout.value)
})
</script>

<template>
  <component :is="currentLayout" :title="module.name">
    <div class="module-shell-content">
      <!-- Header du shell -->
      <div class="shell-header">
        <div class="shell-icon" :style="{ background: moduleColor }">
          <i :class="module.icon || 'ti ti-apps'"></i>
        </div>
        <div class="shell-info">
          <h1 class="shell-title">{{ module.name }}</h1>
          <p class="shell-subtitle">{{ module.service?.name || 'Module' }}</p>
        </div>
      </div>

      <!-- Message de bienvenue -->
      <div class="shell-welcome">
        <div class="welcome-icon">
          <i class="ti ti-rocket"></i>
        </div>
        <h2>Module activé avec succès</h2>
        <p>Utilisez le menu de navigation pour accéder aux fonctionnalités du module.</p>
      </div>

      <!-- Informations du module -->
      <div class="shell-stats">
        <div class="stat-card">
          <i class="ti ti-code"></i>
          <div class="stat-content">
            <span class="stat-label">Code</span>
            <span class="stat-value">{{ module.code }}</span>
          </div>
        </div>

        <div class="stat-card">
          <i class="ti ti-package"></i>
          <div class="stat-content">
            <span class="stat-label">Service</span>
            <span class="stat-value">{{ module.service?.name || 'N/A' }}</span>
          </div>
        </div>

        <div class="stat-card">
          <i class="ti ti-palette"></i>
          <div class="stat-content">
            <span class="stat-label">Layout</span>
            <span class="stat-value">{{ layout?.split('/')[1] || 'Default' }}</span>
          </div>
        </div>
      </div>

      <!-- Debug info (développement seulement) -->
      <details class="shell-debug">
        <summary>🔍 Informations de débogage</summary>
        <pre>{{ JSON.stringify({ module, layout }, null, 2) }}</pre>
      </details>
    </div>
  </component>
</template>

<style scoped>
.module-shell-content {
  padding: 24px;
}

/* Header */
.shell-header {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 32px;
  padding: 24px;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  border-left: 4px solid #10b981;
}

.shell-icon {
  width: 80px;
  height: 80px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 2.5rem;
  flex-shrink: 0;
}

.shell-info {
  flex: 1;
}

.shell-title {
  margin: 0 0 4px;
  font-size: 1.8rem;
  font-weight: 800;
  color: #0f172a;
}

.shell-subtitle {
  margin: 0;
  color: #64748b;
  font-size: 1rem;
}

/* Welcome */
.shell-welcome {
  text-align: center;
  padding: 48px 24px;
  background: linear-gradient(135deg, #f0fdf4, #dcfce7);
  border-radius: 16px;
  margin-bottom: 32px;
}

.welcome-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 20px;
  background: linear-gradient(135deg, #10b981, #059669);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 2.5rem;
}

.shell-welcome h2 {
  margin: 0 0 12px;
  font-size: 1.5rem;
  font-weight: 700;
  color: #0f172a;
}

</style>