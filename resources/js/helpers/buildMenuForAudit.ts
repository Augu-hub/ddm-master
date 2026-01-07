// resources/js/helpers/buildMenuForAudit-PRO.ts
/**
 * AUDIT Menu System - Version PROFESSIONNELLE
 * Inspirée de menu.ts (DIADDEM)
 * 
 * Structure:
 * - Cache multi-niveaux (structure + visibility)
 * - Gestion séparée des données
 * - Composable useAuditMenu hook
 * - Robustesse pro
 */

import type { MenuType } from '@/types/layout'

export type AuditMenuNode = {
  id?: number
  key: string
  label: string
  type?: 'item' | 'title' | 'divider' | null
  icon?: string | null
  url?: string | null
  route_name?: string | null
  target?: string | null
  sort?: number | null
  badge_json?: any
  tooltip_json?: any
  meta_json?: Record<string, unknown> | null
  children?: AuditMenuNode[]
}

export type AuditMenuVisibility = Record<string, boolean>

/**
 * Cache configuration
 */
const CACHE_CONFIG = {
  structure: {
    key: 'auditMenuStructure',
    timeKey: 'auditMenuStructure_time',
    expiry: 60 * 60 * 1000, // 1 heure
  },
  visibility: {
    key: 'auditMenuVisibility',
    timeKey: 'auditMenuVisibility_time',
    expiry: 60 * 60 * 1000, // 1 heure
  },
}

/**
 * Fallback menu par défaut
 */
export const DEFAULT_AUDIT_MENU: MenuType[] = [
  { label: 'AUDIT', key: 'audit-root', isTitle: true, icon: 'ti ti-checklist' },
  { key: 'dashboard', label: 'Dashboard', icon: 'ti ti-layout-dashboard', url: '/dashboard/audit', sort: 1 },
  {
    key: 'risques',
    label: 'Risques',
    icon: 'ti ti-alert-triangle',
    sort: 2,
    children: [
      { key: 'risques_identification', label: 'Identification', url: '/dashboards/risk', parentKey: 'risques', sort: 1 },
      { key: 'risques_matrice', label: 'Matrice', url: '/dashboards/risk#matrice', parentKey: 'risques', sort: 2 },
      { key: 'risques_frequences', label: 'Fréquences', url: '/dashboards/risk#frequences', parentKey: 'risques', sort: 3 },
      { key: 'risques_impacts', label: 'Impacts', url: '/dashboards/risk#impacts', parentKey: 'risques', sort: 4 },
    ],
  },
  {
    key: 'controles',
    label: 'Contrôles',
    icon: 'ti ti-checkbox',
    sort: 3,
    children: [
      { key: 'controles_liste', label: 'Liste', url: '/dashboard/controls', parentKey: 'controles', sort: 1 },
      { key: 'controles_matrice', label: 'Matrice', url: '/dashboard/controls/matrix', parentKey: 'controles', sort: 2 },
    ],
  },
  {
    key: 'rapports',
    label: 'Rapports',
    icon: 'ti ti-file-chart',
    sort: 4,
    children: [
      { key: 'rapports_risques', label: 'Risques', url: '/dashboard/reports/risk', parentKey: 'rapports', sort: 1 },
      { key: 'rapports_controles', label: 'Contrôles', url: '/dashboard/reports/controls', parentKey: 'rapports', sort: 2 },
    ],
  },
  { key: 'param_divider', isDivider: true, sort: 5 },
  {
    key: 'parametres',
    label: 'Paramètres',
    icon: 'ti ti-settings',
    sort: 6,
    children: [
      { key: 'param_projets', label: 'Projets', url: '/param/projects', parentKey: 'parametres', sort: 1 },
      { key: 'param_entites', label: 'Entités', url: '/param/entities', parentKey: 'parametres', sort: 2 },
      { key: 'param_utilisateurs', label: 'Utilisateurs', url: '/param/users', parentKey: 'parametres', sort: 3 },
    ],
  },
]

/**
 * ════════════════════════════════════════════════════════════════════════════
 * CACHE MANAGEMENT (Multi-level)
 * ════════════════════════════════════════════════════════════════════════════
 */

function getFromCache<T>(config: { key: string; timeKey: string; expiry: number }): T | null {
  try {
    const cached = localStorage.getItem(config.key)
    const cachedTime = localStorage.getItem(config.timeKey)

    if (!cached || !cachedTime) return null

    const elapsed = Date.now() - parseInt(cachedTime, 10)
    if (elapsed > config.expiry) {
      clearCache(config)
      return null
    }

    const parsed = JSON.parse(cached) as T
    return parsed
  } catch (error) {
    console.warn(`⚠️ Erreur lecture cache (${config.key}):`, error)
    clearCache(config)
    return null
  }
}

function saveToCache<T>(config: { key: string; timeKey: string }, data: T): void {
  try {
    localStorage.setItem(config.key, JSON.stringify(data))
    localStorage.setItem(config.timeKey, Date.now().toString())
  } catch (error) {
    console.warn(`⚠️ Erreur sauvegarde cache (${config.key}):`, error)
  }
}

function clearCache(config: { key: string; timeKey: string }): void {
  try {
    localStorage.removeItem(config.key)
    localStorage.removeItem(config.timeKey)
  } catch (error) {
    console.warn(`⚠️ Erreur vidage cache (${config.key}):`, error)
  }
}

export function invalidateAuditMenuCache(): void {
  clearCache(CACHE_CONFIG.structure)
  clearCache(CACHE_CONFIG.visibility)
  console.log('♻️ Cache AUDIT invalidé (structure + visibility)')
}

/**
 * ════════════════════════════════════════════════════════════════════════════
 * FETCH FUNCTIONS (Séparation des responsabilités)
 * ════════════════════════════════════════════════════════════════════════════
 */

export async function fetchAuditMenuStructure(
  moduleCode: string = 'audit',
  forceRefresh: boolean = false
): Promise<AuditMenuNode[]> {
  if (!forceRefresh) {
    const cached = getFromCache<AuditMenuNode[]>(CACHE_CONFIG.structure)
    if (cached && cached.length > 0) {
      console.log('✅ AUDIT menu structure chargée depuis cache')
      return cached
    }
  }

  try {
    console.log(`📡 Chargement structure AUDIT depuis API...`)
    
    // Essayer 3 endpoints
    const endpoints = [
      `/api/menu/audit/structure?module=${encodeURIComponent(moduleCode)}`,
      `/api/menu/structure?module=${encodeURIComponent(moduleCode)}`,
      `/api/menus/audit?module=${encodeURIComponent(moduleCode)}`,
    ]

    for (const endpoint of endpoints) {
      try {
        const res = await fetch(endpoint, {
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
          credentials: 'same-origin',
        })

        if (!res.ok) continue

        const data = await res.json()
        const items = Array.isArray(data) ? data : (data?.data ?? data?.structure ?? data?.menus ?? [])

        if (Array.isArray(items) && items.length > 0) {
          console.log(`✅ Structure AUDIT chargée (${items.length} items)`)
          saveToCache(CACHE_CONFIG.structure, items)
          return items
        }
      } catch {
        continue
      }
    }

    return []
  } catch (error) {
    console.error('❌ Erreur chargement structure AUDIT:', error)
    return []
  }
}

export async function fetchAuditMenuVisibility(
  moduleCode: string = 'audit',
  forceRefresh: boolean = false
): Promise<AuditMenuVisibility> {
  if (!forceRefresh) {
    const cached = getFromCache<AuditMenuVisibility>(CACHE_CONFIG.visibility)
    if (cached && Object.keys(cached).length > 0) {
      console.log('✅ AUDIT menu visibility chargée depuis cache')
      return cached
    }
  }

  try {
    console.log(`📡 Chargement visibility AUDIT depuis API...`)
    const res = await fetch(`/api/menu/audit/visibility?module=${encodeURIComponent(moduleCode)}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      credentials: 'same-origin',
    })

    if (!res.ok) return {}

    const data = await res.json()
    const visibility = (data?.visibility ?? {}) as AuditMenuVisibility

    if (Object.keys(visibility).length > 0) {
      console.log(`✅ Visibility AUDIT chargée`)
      saveToCache(CACHE_CONFIG.visibility, visibility)
    }

    return visibility
  } catch (error) {
    console.error('❌ Erreur chargement visibility AUDIT:', error)
    return {}
  }
}

/**
 * ════════════════════════════════════════════════════════════════════════════
 * MAPPING & TRANSFORMATION
 * ════════════════════════════════════════════════════════════════════════════
 */

function deepClone<T>(x: T): T {
  return JSON.parse(JSON.stringify(x))
}

function serverToClient(node: AuditMenuNode, parentKey?: string): MenuType {
  const isTitle = node.type === 'title'
  const isDivider = node.type === 'divider'
  const routes = node.route_name ? [node.route_name] : []

  const item: MenuType = {
    key: node.key,
    label: node.label,
    icon: node.icon ?? undefined,
    isTitle,
    isDivider,
    url: node.url ?? undefined,
    routes,
    parentKey,
    sort: node.sort ?? undefined,
  }

  if (node.children?.length) {
    item.children = node.children
      .sort((a, b) => (a.sort ?? 0) - (b.sort ?? 0))
      .map((c) => serverToClient(c, node.key))
  }

  return item
}

function applyVisibility(items: MenuType[], visibility: AuditMenuVisibility): MenuType[] {
  const allow = (key?: string) =>
    key ? (visibility.hasOwnProperty(key) ? !!visibility[key] : true) : true

  const walk = (nodes: MenuType[]): MenuType[] =>
    (nodes || []).reduce<MenuType[]>((acc, node) => {
      if (!allow(node.key)) return acc

      const next: MenuType = { ...node }
      if (next.children?.length) {
        next.children = walk(next.children)
      }

      acc.push(next)
      return acc
    }, [])

  return walk(items)
}

/**
 * ════════════════════════════════════════════════════════════════════════════
 * MAIN BUILDER (Inspiré de buildMenuForModule)
 * ════════════════════════════════════════════════════════════════════════════
 */

export async function buildAuditMenu(
  moduleCode: string = 'audit',
  options?: { forceRefresh?: boolean }
): Promise<MenuType[]> {
  const forceRefresh = options?.forceRefresh ?? false

  try {
    console.log(`📚 Building AUDIT menu for module: ${moduleCode}`)

    // 1️⃣ Charger la structure
    const serverTree = await fetchAuditMenuStructure(moduleCode, forceRefresh)

    let base: MenuType[] = serverTree.length
      ? serverTree.sort((a, b) => (a.sort ?? 0) - (b.sort ?? 0)).map((n) => serverToClient(n))
      : deepClone(DEFAULT_AUDIT_MENU)

    console.log(`  ├─ Base tree: ${base.length} items`)

    // 2️⃣ Charger la visibilité
    const visibility = await fetchAuditMenuVisibility(moduleCode, forceRefresh)

    // 3️⃣ Appliquer la visibilité
    const visibleTree = applyVisibility(base, visibility)

    console.log(`  └─ Final tree: ${visibleTree.length} items`)
    console.log(`✅ AUDIT menu built successfully`)

    return visibleTree
  } catch (error) {
    console.error('❌ Erreur building AUDIT menu:', error)
    return deepClone(DEFAULT_AUDIT_MENU)
  }
}

/**
 * ════════════════════════════════════════════════════════════════════════════
 * DEBUG & CONFIG
 * ════════════════════════════════════════════════════════════════════════════
 */

export function debugAuditMenuCache(): void {
  console.group('🔍 DEBUG AUDIT Menu Cache')

  // Structure
  const structureCached = localStorage.getItem(CACHE_CONFIG.structure.key)
  const structureTime = localStorage.getItem(CACHE_CONFIG.structure.timeKey)
  console.log('\n📦 Structure:')
  console.log(`  - Cached: ${!!structureCached}`)
  console.log(`  - Time: ${!!structureTime}`)
  if (structureCached) {
    const parsed = JSON.parse(structureCached)
    console.log(`  - Items: ${Array.isArray(parsed) ? parsed.length : 'invalid'}`)
  }

  // Visibility
  const visibilityCached = localStorage.getItem(CACHE_CONFIG.visibility.key)
  const visibilityTime = localStorage.getItem(CACHE_CONFIG.visibility.timeKey)
  console.log('\n📦 Visibility:')
  console.log(`  - Cached: ${!!visibilityCached}`)
  console.log(`  - Time: ${!!visibilityTime}`)
  if (visibilityCached) {
    const parsed = JSON.parse(visibilityCached)
    console.log(`  - Keys: ${Object.keys(parsed).length}`)
  }

  console.groupEnd()
}

/**
 * ════════════════════════════════════════════════════════════════════════════
 * COMPOSABLE (useAuditMenu hook)
 * ════════════════════════════════════════════════════════════════════════════
 */

export function useAuditMenu() {
  return {
    DEFAULT_AUDIT_MENU,
    buildAuditMenu,
    fetchAuditMenuStructure,
    fetchAuditMenuVisibility,
    invalidateAuditMenuCache,
    debugAuditMenuCache,
  }
}

/**
 * ════════════════════════════════════════════════════════════════════════════
 * ALIASES POUR COMPATIBILITÉ
 * ════════════════════════════════════════════════════════════════════════════
 */

// Alias: buildMenuForAudit (ancien nom) → buildAuditMenu (nouveau nom)
export const buildMenuForAudit = buildAuditMenu

/**
 * Default export
 */
export default {
  DEFAULT_AUDIT_MENU,
  buildAuditMenu,
  buildMenuForAudit, // Alias pour compatibilité
  fetchAuditMenuStructure,
  fetchAuditMenuVisibility,
  invalidateAuditMenuCache,
  debugAuditMenuCache,
  useAuditMenu,
}