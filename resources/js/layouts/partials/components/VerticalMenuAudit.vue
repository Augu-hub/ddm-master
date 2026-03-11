<template>
    <ul class="audit-nav">
        <li class="audit-nav-title">ESPACE AUDITEUR</li>

        <li
            v-for="item in menuItems"
            :key="item.label"
            class="audit-nav-item"
            :class="{ active: isActive(item.route) }"
        >
            <component
                :is="item.route ? Link : 'a'"
                :href="item.route ? route(item.route) : '#'"
                class="audit-nav-link"
            >
                <span class="nav-icon">
                    <i :class="item.icon"></i>
                </span>
                <span class="nav-label">{{ item.label }}</span>

                <span v-if="item.badge" class="nav-badge">{{ item.badge }}</span>
            </component>
        </li>
    </ul>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const missionsDemarrables = computed(() =>
    (page.props as any).missionsDemarrables ?? []
);

const menuItems = computed(() => [
    {
        label: "Vue d'ensemble",
        route: 'auditor.dashboard',
        icon:  'ti ti-layout-dashboard',
        badge: null,
    },
    {
        label: 'Mes Missions',
        route: 'auditor.missions.index',
        icon:  'ti ti-briefcase',
        badge: missionsDemarrables.value.length || null,
    },
    {
        label: 'Planning',
        route: '',
        icon:  'ti ti-calendar-stats',
        badge: null,
    },
    {
        label: 'Compétences',
        route: '',
        icon:  'ti ti-award',
        badge: null,
    },
    {
        label: 'Budget',
        route: '',
        icon:  'ti ti-coin',
        badge: null,
    },
]);

function isActive(routeName: string | null | undefined): boolean {
    if (!routeName) return false;
    try {
        const routeUrl = route(routeName).replace(window.location.origin, '');
        return page.url.startsWith(routeUrl);
    } catch {
        return false;
    }
}
</script>

<style scoped>
/* ════════════════════════════════════════
   NAV LIST
   ════════════════════════════════════════ */
.audit-nav {
    list-style: none;
    padding: 8px 0 24px;
    margin: 0;
}

/* Titre section */
.audit-nav-title {
    color: rgba(148, 163, 184, 0.5);
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    padding: 20px 20px 8px;
}

/* Item */
.audit-nav-item {
    margin: 1px 8px;
    border-radius: 8px;
    overflow: hidden;
}

/* Lien */
.audit-nav-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    color: rgba(203, 213, 225, 0.75);
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.18s ease;
    position: relative;
}

/* Hover */
.audit-nav-link:hover {
    color: #e2e8f0;
    background: rgba(255, 255, 255, 0.06);
}

.audit-nav-link:hover .nav-icon i {
    color: #60a5fa;
}

/* Actif */
.audit-nav-item.active .audit-nav-link {
    color: #ffffff;
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.2) 0%, rgba(59, 130, 246, 0.08) 100%);
    box-shadow: inset 3px 0 0 #3b82f6;
}

.audit-nav-item.active .nav-icon i {
    color: #60a5fa;
}

/* Icône */
.nav-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 7px;
    flex-shrink: 0;
    background: rgba(255, 255, 255, 0.04);
    transition: background 0.18s;
}

.nav-icon i {
    font-size: 1.1rem;
    color: rgba(148, 163, 184, 0.7);
    transition: color 0.18s;
}

.audit-nav-item.active .nav-icon {
    background: rgba(59, 130, 246, 0.15);
}

/* Label */
.nav-label {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Badge */
.nav-badge {
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    background: #ef4444;
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
</style>