```vue
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
    </main>
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const modules = [
  {
    key: 'param',
    category: 'Administration',
    title: 'DIADEM PARAM',
    desc: 'Paramétrages globaux, référentiels, agences/tenants, profils et autorisations.',
    meta: 'Dernière config : hier',
    pillBg: 'rgba(37,99,235,.10)',
    pillFg: '#1d4ed8',
    accent: '#3b82f6',
    link: '/param/projects', // Updated to match defined route
  },
  {
    key: 'risque',
    category: 'Gouvernance & Risques',
    title: 'DIADEME RISQUE',
    desc: 'Cartographie, évaluations, plans de traitement, suivi des indicateurs.',
    meta: '3 risques à revoir',
    pillBg: 'rgba(16,185,129,.12)',
    pillFg: '#047857',
    accent: '#10b981',
    link: '/dashboards/risque', // Updated to match new route
  },
  {
    key: 'process',
    category: 'Organisation',
    title: 'DIADEM PROCESSUS',
    desc: 'Modélisez vos processus, contrôles clés, matrices RACI et procédures.',
    meta: '2 processus modifiés',
    pillBg: 'rgba(124,58,237,.12)',
    pillFg: '#6d28d9',
    accent: '#8b5cf6',
    link: '/dashboards/processus', // Updated to match new route
  },
  {
    key: 'audi',
    category: 'Audit Interne',
    title: 'DIADEM AUDI',
    desc: 'Plan d’audit, missions, constats et suivi des recommandations.',
    meta: '1 mission en cours',
    pillBg: 'rgba(245,158,11,.14)',
    pillFg: '#92400e',
    accent: '#f59e0b',
    link: '/dashboards/audi', // Updated to match new route
  },
];
</script>

<style scoped>
.page {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f3f4f6 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px;
}

.wrap {
  width: min(1080px, 100%);
}

.header {
  text-align: center;
  padding: 16px 0;
  background: #fff;
  border-radius: 12px;
  margin-bottom: 24px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}

.h {
  margin: 0;
  font-weight: 700;
  letter-spacing: 0.1px;
  font-size: clamp(18px, 3.2vw, 28px);
  color: #0f172a;
}

.grid {
  display: grid;
  gap: 24px;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

@media (max-width: 720px) {
  .grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
}

.card {
  background: #fff;
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.06);
  box-shadow: 0 10px 28px rgba(2, 132, 199, 0.06);
  padding: 24px;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  text-decoration: none;
  display: block;
  position: relative;
  color: inherit; /* Ensure text color is inherited */
}

.card:hover {
  transform: translateY(-4px) scale(1.02);
  box-shadow: 0 20px 40px rgba(2, 132, 199, 0.14);
  border-color: color-mix(in oklab, var(--accent) 40%, transparent);
}

.pill {
  display: inline-block;
  padding: 0.4rem 0.7rem;
  border-radius: 999px;
  background: var(--pill-bg);
  color: var(--pill-fg);
  font-weight: 700;
  font-size: 0.8rem;
  letter-spacing: 0.2px;
}

.ttl {
  margin: 0.8rem 0 0.3rem;
  font-size: 1.2rem;
  font-weight: 800;
  color: #0f172a;
}

.desc {
  margin: 0 0 1rem;
  color: #475569;
  line-height: 1.5;
  font-size: 0.95rem;
}

.meta {
  font-size: 0.9rem;
  color: #0f172a;
  font-weight: 600;
  border-top: 1px dashed color-mix(in oklab, var(--accent) 30%, #e5e7eb);
  padding-top: 0.8rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.arrow {
  color: var(--accent);
  font-size: 1rem;
  opacity: 0.7;
  transition: opacity 0.2s ease;
}

.card:hover .arrow {
  opacity: 1;
}
</style>
