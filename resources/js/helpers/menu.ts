// resources/js/helpers/menu.ts
import type { MenuType } from '@/types/layout'

/** ======================= Configuration des routes par menu ======================= */
export const menuRoutes: Record<string, string[]> = {
  'dashboard': ['dashboard', 'dashboard-param', 'dashboard-processus', 'dashboard-risque'],
  'project': ['param.projects.index', 'param.projects.create', 'param.projects.edit', 'param.projects.show'],
  'entity': ['param.entities.index', 'param.entities.create', 'param.entities.edit', 'param.entities.show'],
  'process': ['param.mpa.index', 'param.macro.store', 'param.process.store', 'param.activity.store'],
  'function': ['param.function.index', 'param.function.store', 'param.function.update', 'param.function.destroy'],
  'entity_chart': ['param.charts.entity'],
  'function_chart': ['param.charts.function'],
  'distribution': ['param.distribution.index', 'param.distribution.tree', 'param.distribution.current'],
  'charts_entity': ['param.charts.entity.functions.index', 'param.charts.entity.functions.show'],
  'permissions': ['admin.permissions.index', 'admin.permissions.store', 'admin.permissions.syncRole'],
  'roles': ['admin.roles.index', 'admin.roles.store', 'admin.roles.syncUserRoles'],
  'users': ['admin.users.index', 'admin.users.create', 'admin.users.edit', 'admin.users.store', 'admin.users.update'],
  'menus': ['admin.menus.index', 'admin.menus.create', 'admin.menus.edit', 'admin.menus.store', 'admin.menus.update'],
  'i18n': ['admin.translations.index', 'admin.translations.create', 'admin.translations.edit'],
  'tenants': ['admin.tenants.index', 'admin.tenants.store', 'admin.tenants.update', 'admin.tenants.destroy', 'admin.tenants.syncUsers'],
  'ddm-process': ['modules.processus.index', 'modules.processus.create', 'modules.processus.edit'],
  'ddm-risks': ['modules.risks.index', 'modules.risks.create', 'modules.risks.edit'],
  'ddm-audit': ['modules.audit.index', 'modules.audit.create', 'modules.audit.edit']
}

/** ======================= Fallback statique aligné avec les routes ======================= */
export const menu: MenuType[] = [
  { 
    label: 'DIADDEM', 
    key: 'seed-root', 
    isTitle: true, 
    icon: 'ti ti-database-cog' 
  },

  { 
    key: 'dashboard', 
    label: 'Tableau de bord', 
    icon: 'ti ti-layout-dashboard', 
    url: '/',
    routes: menuRoutes.dashboard
  },

  {
    key: 'param',
    label: 'Paramétrage',
    icon: 'ti ti-adjustments',
    routes: [],
    children: [
      { 
        key: 'project',  
        label: 'Projets', 
        icon: 'ti ti-folders',            
        parentKey: 'param', 
        url: '/param/projects',
        routes: menuRoutes.project
      },
      { 
        key: 'entity',   
        label: 'Entités', 
        icon: 'ti ti-building-community',  
        parentKey: 'param', 
        url: '/param/entities',
        routes: menuRoutes.entity
      },
      { 
        key: 'process',  
        label: 'Macro-Processus-Activité', 
        icon: 'ti ti-sitemap', 
        parentKey: 'param', 
        url: '/param/mpa',
        routes: menuRoutes.process
      },
      { 
        key: 'function', 
        label: 'Fonctions', 
        icon: 'ti ti-components',        
        parentKey: 'param', 
        url: '/param/function',
        routes: menuRoutes.function
      },

      {
        key: 'charts',
        label: 'Organigrammes Projet',
        icon: 'ti ti-chart-infographic',
        parentKey: 'param',
        routes: [],
        children: [
          { 
            key: 'entity_chart',   
            label: 'Organigramme Entités',   
            icon: 'ti ti-building-skyscraper', 
            parentKey: 'charts', 
            url: '/param/charts/entity',
            routes: menuRoutes.entity_chart
          },
          { 
            key: 'function_chart', 
            label: 'Organigramme Fonctions', 
            icon: 'ti ti-hierarchy',            
            parentKey: 'charts', 
            url: '/param/charts/function',
            routes: menuRoutes.function_chart
          },
        ],
      },

      { 
        key: 'distribution', 
        label: 'Répartition MPA/fonction', 
        icon: 'ti ti-arrows-shuffle', 
        parentKey: 'param', 
        url: '/param/distribution',
        routes: menuRoutes.distribution
      },

      // sera rempli dynamiquement avec les entités autorisées
      { 
        key: 'charts_entity', 
        label: 'Organigrammes Entité', 
        icon: 'ti ti-chart-infographic', 
        parentKey: 'param', 
        routes: menuRoutes.charts_entity,
        children: [] 
      },
    ],
  },

  {
    key: 'builtin',
    label: 'Administration',
    icon: 'ti ti-settings',
    routes: [],
    children: [
      { 
        key: 'permissions', 
        label: 'Permissions',  
        icon: 'ti ti-key',          
        parentKey: 'builtin', 
        url: '/admin/permissions',
        routes: menuRoutes.permissions
      },
      { 
        key: 'roles',       
        label: 'Rôles',        
        icon: 'ti ti-user-shield',   
        parentKey: 'builtin', 
        url: '/admin/roles',
        routes: menuRoutes.roles
      },
      { 
        key: 'users',       
        label: 'Utilisateurs', 
        icon: 'ti ti-users',         
        parentKey: 'builtin', 
        url: '/admin/users',
        routes: menuRoutes.users
      },
      { 
        key: 'menus',       
        label: 'Menus',        
        icon: 'ti ti-menu-2',        
        parentKey: 'builtin', 
        url: '/admin/menus',
        routes: menuRoutes.menus
      },
      { 
        key: 'i18n',        
        label: 'Traductions',  
        icon: 'ti ti-language',      
        parentKey: 'builtin', 
        url: '/admin/translations',
        routes: menuRoutes.i18n
      },
      { 
        key: 'tenants',     
        label: 'Clients',      
        icon: 'ti ti-building-store',
        parentKey: 'builtin', 
        url: '/admin/tenants',
        routes: menuRoutes.tenants,
        badge: { variant: 'primary', text: 'NEW' }
      },
    ],
  },

  {
    key: 'modules',
    label: 'Modules Métier',
    icon: 'ti ti-apps',
    routes: [],
    children: [
      { 
        key: 'ddm-process', 
        label: 'DDM Processus',     
        icon: 'ti ti-flow-branch',    
        parentKey: 'modules', 
        url: '/modules/processus',
        routes: menuRoutes['ddm-process']
      },
      { 
        key: 'ddm-risks',   
        label: 'DDM Risques',       
        icon: 'ti ti-alert-triangle', 
        parentKey: 'modules', 
        url: '/modules/risks',
        routes: menuRoutes['ddm-risks']
      },
      { 
        key: 'ddm-audit',   
        label: 'DDM Audit Interne', 
        icon: 'ti ti-checklist',      
        parentKey: 'modules', 
        url: '/modules/audit',
        routes: menuRoutes['ddm-audit']
      },
    ],
  },

  {
    label: 'Dashboards', 
    key: 'dashboards', 
    isTitle: true, 
    icon: 'ti ti-dashboard',
    children: [
      { 
        url: '/',                    
        label: 'Sales',   
        icon: 'ti ti-dashboard',          
        parentKey: 'dashboards', 
        badge: { variant: 'success', text: '5' },
        routes: ['dashboard']
      },
      { 
        url: '/dashboards/clinic',   
        label: 'Clinic',  
        icon: 'ti ti-building-hospital',  
        parentKey: 'dashboards',
        routes: [] 
      },
      { 
        url: '/dashboards/e-wallet', 
        label: 'eWallet', 
        icon: 'ti ti-wallet',             
        parentKey: 'dashboards', 
        tooltip: { variant: 'danger', icon: 'ti ti-info-triangle', text: 'Your wallet balance is <b>low!</b>' },
        routes: []
      },
    ],
  },
]

/** ======================= API visibilité ======================= */
export type MenuVisibility = Record<string, boolean>

export async function fetchMenuVisibility(): Promise<MenuVisibility> {
  try {
    const res = await fetch('/api/menu/visibility', { 
      headers: { 
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      } 
    })
    if (!res.ok) return {}
    const data = await res.json()
    return (data?.visibility ?? {}) as MenuVisibility
  } catch {
    return {}
  }
}

/** ======================= Gestion des routes actives ======================= */
export function isRouteActive(currentRoute: string, menuItemRoutes: string[] = []): boolean {
  if (!menuItemRoutes.length) return false
  
  // Vérification exacte
  if (menuItemRoutes.includes(currentRoute)) return true
  
  // Vérification partielle pour les routes paramétrées
  return menuItemRoutes.some(route => {
    if (route.includes('.')) {
      // Pour les routes nommées comme 'admin.tenants.edit'
      const routeBase = route.split('.').slice(0, -1).join('.')
      const currentBase = currentRoute.split('.').slice(0, -1).join('.')
      return routeBase === currentBase
    }
    return false
  })
}

export function findActiveMenuKey(currentRoute: string): string | null {
  const findInTree = (items: MenuType[]): string | null => {
    for (const item of items) {
      if (item.routes && isRouteActive(currentRoute, item.routes)) {
        return item.key
      }
      if (item.children) {
        const childResult = findInTree(item.children)
        if (childResult) return childResult
      }
    }
    return null
  }
  
  return findInTree(menu)
}

/** ======================= Gestion des entités dynamiques ======================= */
export interface Entity {
  id: number | string
  name: string
  slug?: string
}

/** ======================= Helpers ======================= */
function deepClone<T>(obj: T): T {
  return JSON.parse(JSON.stringify(obj))
}

/**
 * Injecte les entités dynamiques dans le menu "Organigrammes Entité"
 */
function injectEntityChildren(base: MenuType[], entities: Entity[] = []) {
  const param = base.find(i => i.key === 'param')
  const chartsEntity = param?.children?.find(i => i.key === 'charts_entity')
  
  if (!chartsEntity) return
  
  // Réinitialiser les enfants
  chartsEntity.children = []
  
  // Ajouter les entités dynamiquement
  if (entities && entities.length > 0) {
    chartsEntity.children = entities.map((entity, index) => ({
      key: `entity_functions_${entity.id}`,
      label: entity.name,
      icon: 'ti ti-building-skyscraper',
      parentKey: 'charts_entity',
      url: `/param/charts/entity-functions/${entity.id}`,
      routes: ['param.charts.entity.functions.show'],
      sort: index
    }))
    
    // Ajouter un séparateur si on a des entités
    chartsEntity.children.push({
      key: 'charts_entity_divider',
      label: '',
      parentKey: 'charts_entity',
      isDivider: true
    })
  }
  
  // Toujours ajouter l'option "Toutes les entités"
  chartsEntity.children.push({
    key: 'entity_functions_all',
    label: 'Toutes les entités',
    icon: 'ti ti-chart-infographic',
    parentKey: 'charts_entity',
    url: '/param/charts/entity-functions',
    routes: ['param.charts.entity.functions.index'],
    badge: entities && entities.length > 0 ? { variant: 'info', text: entities.length.toString() } : undefined
  })
}

/**
 * Filtre récursif selon visibility
 */
function applyVisibility(items: MenuType[], visibility: MenuVisibility): MenuType[] {
  const allow = (key?: string) =>
    key ? (visibility.hasOwnProperty(key) ? !!visibility[key] : true) : true

  const walk = (nodes: MenuType[], parentKey?: string): MenuType[] =>
    (nodes || []).reduce<MenuType[]>((acc, node) => {
      if (!allow(node.key)) return acc
      
      const next: MenuType = { ...node }
      
      if (next.children?.length) {
        next.children = walk(next.children, next.key)
        // Si c'est un titre et aucun enfant autorisé -> on masque le titre
        if (next.isTitle && (!next.children || next.children.length === 0)) return acc
        // Si c'est un parent normal et aucun enfant -> on masque aussi
        if (!next.isTitle && (!next.children || next.children.length === 0)) return acc
      }
      
      acc.push(next)
      return acc
    }, [])

  return walk(items)
}

/**
 * Vérifie si l'utilisateur a accès au menu des tenants
 */
function shouldShowTenantsMenu(user: any): boolean {
  if (!user) return false
  
  // Admin global a toujours accès
  if (user.is_global_admin) return true
  
  // Vérifier les permissions
  return user.permissions?.includes('admin.tenants.view') || 
         user.roles?.some((role: any) => role.name === 'Super Admin')
}

/**
 * Filtre les menus selon les permissions utilisateur
 */
function filterMenuByPermissions(items: MenuType[], user: any): MenuType[] {
  const walk = (nodes: MenuType[]): MenuType[] =>
    nodes.reduce<MenuType[]>((acc, node) => {
      // Vérifier spécifiquement le menu tenants
      if (node.key === 'tenants' && !shouldShowTenantsMenu(user)) {
        return acc
      }
      
      const next: MenuType = { ...node }
      
      if (next.children?.length) {
        next.children = walk(next.children)
        // Masquer si plus d'enfants après filtrage
        if (!next.isTitle && (!next.children || next.children.length === 0)) return acc
        if (next.isTitle && (!next.children || next.children.length === 0)) return acc
      }
      
      acc.push(next)
      return acc
    }, [])

  return walk(items)
}

/** ======================= API principale côté front ======================= */

/**
 * Construit le menu complet avec entités dynamiques et permissions
 */
export async function buildMenuWithEntities(
  entities: Entity[] = [], 
  user: any = null
): Promise<MenuType[]> {
  // Cloner la structure de base
  const base = deepClone(menu)
  
  // Injecter les entités dynamiques
  injectEntityChildren(base, entities)
  
  // Appliquer les permissions utilisateur
  const filteredMenu = user ? filterMenuByPermissions(base, user) : base
  
  // Récupérer la visibilité depuis l'API
  const visibility = await fetchMenuVisibility()
  
  // Appliquer la visibilité
  return applyVisibility(filteredMenu, visibility)
}

/**
 * Met à jour le menu avec les entités (version synchrone)
 */
export const updateMenuWithEntities = (
  entities: Entity[] = [], 
  user: any = null
): MenuType[] => {
  const updatedMenu = deepClone(menu)
  injectEntityChildren(updatedMenu, entities)
  return user ? filterMenuByPermissions(updatedMenu, user) : updatedMenu
}

/**
 * Récupère les entités depuis l'API
 */
export async function fetchEntities(): Promise<Entity[]> {
  try {
    const res = await fetch('/api/menu/entities', {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    })
    
    if (!res.ok) return []
    
    const data = await res.json()
    return data.entities || data || []
  } catch (error) {
    console.error('Erreur lors de la récupération des entités:', error)
    return []
  }
}

/**
 * Hook pour la gestion du menu dans les composants
 */
export function useMenu() {
  return {
    menu,
    menuRoutes,
    isRouteActive,
    findActiveMenuKey,
    buildMenuWithEntities,
    updateMenuWithEntities,
    fetchEntities,
    fetchMenuVisibility
  }
}

/**
 * Export des types pour une meilleure typage
 */
export interface MenuBadge {
  variant: 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info' | 'light' | 'dark'
  text: string
}

export interface MenuTooltip {
  variant: 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info' | 'light' | 'dark'
  icon: string
  text: string
}

// Export par défaut pour une utilisation facile
export default {
  menu,
  menuRoutes,
  isRouteActive,
  findActiveMenuKey,
  buildMenuWithEntities,
  updateMenuWithEntities,
  fetchEntities,
  fetchMenuVisibility,
  useMenu
}