<template>
  <div class="page">
    <main class="wrap">
      <header class="header">
        <h2 class="h">Accéder à un module</h2>
      </header>

      <div class="grid">
        <Link
          v-for="m in modules"
          :key="m.key"
          :href="m.link"
          class="card"
          :style="{
            '--pill-bg': m.pillBg,
            '--pill-fg': m.pillFg,
            '--accent': m.accent
          }"
        >
          <span class="pill">{{ m.category }}</span>
          <h3 class="ttl">{{ m.title }}</h3>
          <p class="desc">{{ m.desc }}</p>
          <div class="meta">
            {{ m.meta }}
            <span class="arrow">→</span>
          </div>
        </Link>
      </div>

      <!-- Debug info (retirer en production) -->
      <div v-if="showDebug" class="debug-panel">
        <h3>🔍 Debug Info</h3>
        <div class="debug-section">
          <h4>Raw modules from backend:</h4>
          <pre>{{ JSON.stringify(rawModules, null, 2) }}</pre>
        </div>
        <div class="debug-section">
          <h4>Computed modules with links:</h4>
          <pre>{{ JSON.stringify(modules.map(m => ({ code: m.key, title: m.title, link: m.link })), null, 2) }}</pre>
        </div>
        <div class="debug-section">
          <h4>User info:</h4>
          <pre>{{ JSON.stringify(user, null, 2) }}</pre>
        </div>
      </div>
      <button @click="showDebug = !showDebug" class="debug-toggle">
        {{ showDebug ? '🔼 Masquer Debug' : '🔽 Afficher Debug' }}
      </button>
    </main>
  </div>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { computed, ref, onMounted } from 'vue'

// Types
type User = { 
  id: number
  name: string
  email: string
  is_global_admin?: boolean
  tenant?: { id: number; name: string } | null 
}

type ModuleDto = { 
  code: string
  name: string
  entry_route?: string | null
  service?: { name?: string | null } | null 
}

// Props from Inertia
const page = usePage()
const rawModules = computed(() => (page.props.modules ?? []) as ModuleDto[])
const user = computed(() => (page.props as any)?.auth?.user as User | null)

// Debug state
const showDebug = ref(false)

// Styles par module
const MAP: Record<string, { 
  category: string
  desc: string
  meta: string
  pillBg: string
  pillFg: string
  accent: string 
}> = {
  'param.projects': { 
    category: 'Administration',
    desc: 'Paramétrages globaux, référentiels, agences/tenants, profils et autorisations.',
    meta: 'Dernière config : hier',
    pillBg: 'rgba(37,99,235,.10)',
    pillFg: '#1d4ed8',
    accent: '#3b82f6'
  },
  'risk.core': {
    category: 'Gouvernance & Risques',
    desc: 'Cartographie, évaluations, plans de traitement, suivi des indicateurs.',
    meta: '3 risques à revoir',
    pillBg: 'rgba(16,185,129,.12)',
    pillFg: '#047857',
    accent: '#10b981'
  },
  'process.core': {
    category: 'Organisation',
    desc: 'Modélisez vos processus, contrôles clés, matrices RACI et procédures.',
    meta: '2 processus modifiés',
    pillBg: 'rgba(124,58,237,.12)',
    pillFg: '#6d28d9',
    accent: '#8b5cf6'
  },
  'audit.core': {
    category: 'Audit Interne',
    desc: 'Plan d\'audit, missions, constats et suivi des recommandations.',
    meta: '1 mission en cours',
    pillBg: 'rgba(245,158,11,.14)',
    pillFg: '#92400e',
    accent: '#f59e0b'
  },
}

// 🔥 CORRECTION CRITIQUE : Toujours pointer vers /m/{code}
const modules = computed(() => {
  console.log('📦 Dashboard: Modules bruts reçus:', rawModules.value)
  
  return rawModules.value.map(m => {
    const s = MAP[m.code] ?? {
      category: m.service?.name ?? 'Application',
      desc: 'Accédez au module.',
      meta: '',
      pillBg: 'rgba(2,132,199,.10)',
      pillFg: '#075985',
      accent: '#06b6d4',
    }

    // 🚀 TOUJOURS utiliser /m/{code} pour déclencher bind.module
    const link = `/m/${m.code}`
    
    console.log(`✅ Module ${m.code} → lien généré: ${link}`)

    return {
      key: m.code,
      category: s.category,
      title: m.name,
      desc: s.desc,
      meta: s.meta,
      pillBg: s.pillBg,
      pillFg: s.pillFg,
      accent: s.accent,
      link,
    }
  })
})

// Log au montage
onMounted(() => {
  console.log('🏠 Dashboard monté')
  console.log('👤 User:', user.value)
  console.log('📦 Modules:', modules.value)
})
</script>

<style scoped>
.page{min-height:100vh;background:linear-gradient(180deg,#f8fafc 0%,#f3f4f6 100%);display:flex;align-items:center;justify-content:center;padding:32px}
.wrap{width:min(1080px,100%);position:relative;padding-bottom:80px}
.header{text-align:center;padding:16px 0;background:#fff;border-radius:12px;margin-bottom:24px;box-shadow:0 4px 12px rgba(0,0,0,.04)}
.h{margin:0;font-weight:700;letter-spacing:.1px;font-size:clamp(18px,3.2vw,28px);color:#0f172a}
.grid{display:grid;gap:24px;grid-template-columns:repeat(2,minmax(0,1fr))}
@media (max-width:720px){.grid{grid-template-columns:1fr;gap:16px}}
.card{background:#fff;border-radius:16px;border:1px solid rgba(0,0,0,.06);box-shadow:0 10px 28px rgba(2,132,199,.06);padding:24px;transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease;text-decoration:none;display:block;position:relative;color:inherit}
.card:hover{transform:translateY(-4px) scale(1.02);box-shadow:0 20px 40px rgba(2,132,199,.14);border-color:color-mix(in oklab,var(--accent) 40%,transparent)}
.pill{display:inline-block;padding:.4rem .7rem;border-radius:999px;background:var(--pill-bg);color:var(--pill-fg);font-weight:700;font-size:.8rem;letter-spacing:.2px}
.ttl{margin:.8rem 0 .3rem;font-size:1.2rem;font-weight:800;color:#0f172a}
.desc{margin:0 0 1rem;color:#475569;line-height:1.5;font-size:.95rem}
.meta{font-size:.9rem;color:#0f172a;font-weight:600;border-top:1px dashed color-mix(in oklab,var(--accent) 30%,#e5e7eb);padding-top:.8rem;display:flex;justify-content:space-between;align-items:center}
.arrow{color:var(--accent);font-size:1rem;opacity:.7;transition:opacity .2s ease}
.card:hover .arrow{opacity:1}

/* Debug Panel */
.debug-panel {
  position: fixed;
  bottom: 60px;
  right: 20px;
  background: #0f172a;
  color: #e2e8f0;
  padding: 20px;
  border-radius: 12px;
  max-width: 500px;
  max-height: 70vh;
  overflow: auto;
  font-size: 12px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.5);
  z-index: 9999;
  font-family: 'Monaco', 'Courier New', monospace;
}

.debug-panel h3 {
  margin: 0 0 16px;
  color: #10b981;
  font-size: 16px;
  font-weight: 700;
}

.debug-section {
  margin-bottom: 20px;
  padding-bottom: 20px;
  border-bottom: 1px solid #334155;
}

.debug-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.debug-section h4 {
  margin: 0 0 8px;
  color: #fbbf24;
  font-size: 13px;
  font-weight: 600;
}

.debug-panel pre {
  margin: 0;
  padding: 12px;
  background: #1e293b;
  border-radius: 6px;
  white-space: pre-wrap;
  word-wrap: break-word;
  line-height: 1.5;
  color: #cbd5e1;
  overflow-x: auto;
}

.debug-toggle {
  position: fixed;
  bottom: 20px;
  right: 20px;
  background: #0f172a;
  color: #10b981;
  border: 2px solid #10b981;
  padding: 12px 20px;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 700;
  font-size: 13px;
  box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
  z-index: 10000;
  transition: all 0.2s;
}

.debug-toggle:hover {
  background: #1e293b;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}
</style>