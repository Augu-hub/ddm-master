// resources/js/helpers/buildMenuForAudit.ts
/**
 * AUDIT Menu Builder - AUTONOME & COMPLET
 * 
 * ✅ Charge UNIQUEMENT les menus AUDIT
 * ✅ Pas de dépendance à buildMenuForModule
 * ✅ Appel simple /api-simple/audit-menus
 * ✅ Cache localStorage 1h
 * ✅ Hiérarchie complète (parents → children)
 */

import type { MenuType } from '@/types/layout'

/**
 * ════════════════════════════════════════════════════════════════════════════
 * TYPES
 * ════════════════════════════════════════════════════════════════════════════
 */

interface ServerMenuNode {
  id: number
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
  meta_json?: any
  children?: ServerMenuNode[]
}

/**
 * ════════════════════════════════════════════════════════════════════════════
 * CACHE CONFIGURATION
 * ════════════════════════════════════════════════════════════════════════════
 */

const CACHE_CONFIG = {
  key: 'auditMenuStructure',
  timeKey: 'auditMenuStructure_time',
  expiry: 60 * 60 * 1000, // 1 heure
}

/**
 * ════════════════════════════════════════════════════════════════════════════
 * CACHE UTILITIES
 * ════════════════════════════════════════════════════════════════════════════
 */

function getFromCache<T>(key: string, timeKey: string, expiry: number): T | null {
  try {
    const cached = localStorage.getItem(key)
    const cachedTime = localStorage.getItem(timeKey)

    if (!cached || !cachedTime) {
      return null
    }

    const elapsed = Date.now() - parseInt(cachedTime, 10)
    if (elapsed > expiry) {
      localStorage.removeItem(key)
      localStorage.removeItem(timeKey)
      return null
    }

    return JSON.parse(cached) as T
  } catch (error) {
    console.warn('⚠️ Cache read error:', error)
    return null
  }
}

function saveToCache<T>(key: string, timeKey: string, data: T): void {
  try {
    localStorage.setItem(key, JSON.stringify(data))
    localStorage.setItem(timeKey, Date.now().toString())
  } catch (error) {
    console.warn('⚠️ Cache save error:', error)
  }
}

/**
 * ════════════════════════════════════════════════════════════════════════════
 * TRANSFORMATION - SERVER TO CLIENT
 * ════════════════════════════════════════════════════════════════════════════
 */

/**
 * Convertir ServerMenuNode → MenuType (récursif pour enfants)
 */
function serverToClient(node: ServerMenuNode, parentKey?: string): MenuType {
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
    badge: node.badge_json ?? undefined,
    tooltip: node.tooltip_json ?? undefined,
    sort: node.sort ?? undefined,
  }

  // Récursif pour enfants
  if (node.children?.length) {
    item.children = node.children
      .sort((a, b) => (a.sort ?? 0) - (b.sort ?? 0))
      .map(c => serverToClient(c, node.key))
  }

  return item
}

/**
 * ════════════════════════════════════════════════════════════════════════════
 * MAIN FUNCTION - CHARGE UNIQUEMENT AUDIT DIRECTEMENT
 * ════════════════════════════════════════════════════════════════════════════
 */

/**
 * Charge les menus AUDIT UNIQUEMENT et DIRECTEMENT depuis /api-simple/audit-menus
 * 
 * ✅ SEULEMENT module AUDIT
 * ✅ Tous les enfants (children) chargés
 * ✅ Cache localStorage 1h
 * ✅ Hiérarchie complète (parents → children)
 * 
 * @param options - forceRefresh pour ignorer le cache
 * @returns MenuType[] - Menus AUDIT
 */
export async function buildMenuForAudit(options?: {
  forceRefresh?: boolean
}): Promise<MenuType[]> {
  try {
    console.log(`\n📚 buildMenuForAudit() - AUDIT UNIQUEMENT`)

    // 1️⃣ Vérifier cache (sauf forceRefresh)
    if (!options?.forceRefresh) {
      const cached = getFromCache<MenuType[]>(
        CACHE_CONFIG.key,
        CACHE_CONFIG.timeKey,
        CACHE_CONFIG.expiry
      )
      if (cached && cached.length > 0) {
        console.log(`✅ Menus AUDIT depuis cache (${cached.length} items, 1h)`)
        return cached
      }
    }

    console.log(`  ├─ Loading from BD via /api-simple/audit-menus...`)

    // 2️⃣ Appeler l'endpoint simple pour charger les menus AUDIT
    const response = await fetch('/api-simple/audit-menus', {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      credentials: 'same-origin',
    })

    if (!response.ok) {
      throw new Error(`❌ HTTP ${response.status}: ${response.statusText}`)
    }

    const data = await response.json()

    // 3️⃣ Extraire les menus depuis la réponse
    const structure = (Array.isArray(data?.data) ? data.data : []) as ServerMenuNode[]

    console.log(`  ├─ Received ${structure.length} menus AUDIT`)

    // 4️⃣ Valider qu'on a des menus
    if (!structure || structure.length === 0) {
      console.warn('⚠️ No AUDIT menus received (BD empty or no permissions)')
      return []
    }

    // 5️⃣ Convertir server → client
    const clientMenus = structure
      .sort((a, b) => (a.sort ?? 0) - (b.sort ?? 0))
      .map(node => serverToClient(node))

    console.log(`  ├─ Converted ${clientMenus.length} nodes to MenuType`)

    // 6️⃣ Cacher le résultat
    saveToCache(CACHE_CONFIG.key, CACHE_CONFIG.timeKey, clientMenus)
    console.log(`  └─ Cached (expires in 1h)`)

    console.log(`✅ AUDIT menus loaded: ${clientMenus.length} items\n`)

    return clientMenus

  } catch (error) {
    console.error('❌ Error in buildMenuForAudit:', error)
    // Retourner tableau vide au lieu de throw pour éviter crashes
    return []
  }
}

/**
 * ════════════════════════════════════════════════════════════════════════════
 * PUBLIC API - CACHE MANAGEMENT & DEBUG
 * ════════════════════════════════════════════════════════════════════════════
 */

/**
 * Invalide le cache et force un reload depuis l'API
 */
export function invalidateAuditMenuCache(): void {
  console.log('♻️ Invalidating AUDIT menu cache')
  localStorage.removeItem(CACHE_CONFIG.key)
  localStorage.removeItem(CACHE_CONFIG.timeKey)
}

/**
 * Affiche le debug du cache dans la console
 */
export function debugAuditMenuCache(): void {
  console.group('🔍 DEBUG: AUDIT Menu Cache')

  const cached = localStorage.getItem(CACHE_CONFIG.key)
  const cachedTime = localStorage.getItem(CACHE_CONFIG.timeKey)

  console.log('Cache key:', CACHE_CONFIG.key)
  console.log('Cached:', !!cached)
  console.log('Time key:', !!cachedTime)

  if (cached) {
    try {
      const parsed = JSON.parse(cached) as MenuType[]
      console.log('Type:', Array.isArray(parsed) ? 'Array' : typeof parsed)
      console.log('Count:', Array.isArray(parsed) ? parsed.length : 'invalid')
      console.log('Data:', parsed)
    } catch (e) {
      console.log('❌ Cache JSON invalid:', e)
    }
  }

  if (cachedTime) {
    const elapsed = Date.now() - parseInt(cachedTime, 10)
    const minutes = Math.floor(elapsed / 1000 / 60)
    const remaining = 60 - minutes
    console.log(`Age: ${minutes}min (expires in ${remaining}min)`)
  }

  console.groupEnd()
}

/**
 * ════════════════════════════════════════════════════════════════════════════
 * COMPOSABLE HOOK
 * ════════════════════════════════════════════════════════════════════════════
 */

/**
 * Vue composable hook pour utiliser les menus AUDIT
 */
export function useAuditMenu() {
  return {
    buildMenuForAudit,
    invalidateAuditMenuCache,
    debugAuditMenuCache,
  }
}

/**
 * Default export
 */
export default {
  buildMenuForAudit,
  invalidateAuditMenuCache,
  debugAuditMenuCache,
  useAuditMenu,
}