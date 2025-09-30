// resources/js/helpers/menu.ts
import type { MenuType } from '@/types/layout'

/** ======================= Fallback statique ======================= */
export const menu: MenuType[] = [
  { label: 'DIADDEM', key: 'seed-root', isTitle: true, icon: 'ti ti-database-cog' },

  { key: 'dashboard', label: 'Tableau de bord', icon: 'ti ti-layout-dashboard', url: '/' },

  {
    key: 'param',
    label: 'Paramétrage',
    icon: 'ti ti-adjustments',
    children: [
      { key: 'project',  label: 'Projets', icon: 'ti ti-folders',            parentKey: 'param', url: '/param/projects' },
      { key: 'entity',   label: 'Entités', icon: 'ti ti-building-community',  parentKey: 'param', url: '/param/entities' },
      { key: 'process',  label: 'Macro-Processus-Activité', icon: 'ti ti-sitemap', parentKey: 'param', url: '/param/process' },
      { key: 'function', label: 'Fonctions', icon: 'ti ti-components',        parentKey: 'param', url: '/param/function' },

      {
        key: 'charts',
        label: 'Organigrammes Projet',
        icon: 'ti ti-chart-infographic',
        parentKey: 'param',
        children: [
          { key: 'entity_chart',   label: 'Organigramme Entités',   icon: 'ti ti-building-skyscraper', parentKey: 'charts', url: '/param/charts/entity' },
          { key: 'function_chart', label: 'Organigramme Fonctions', icon: 'ti ti-hierarchy',            parentKey: 'charts', url: '/param/charts/function' },
        ],
      },

      { key: 'distribution', label: 'Répartition MPA/fonction', icon: 'ti ti-arrows-shuffle', parentKey: 'param', url: '/param/distribution' },

      // sera rempli dynamiquement avec les entités autorisées
      { key: 'charts_entity', label: 'Organigrammes Entité', icon: 'ti ti-chart-infographic', parentKey: 'param', children: [] },
    ],
  },

  {
    key: 'builtin',
    label: 'Administration',
    icon: 'ti ti-settings',
    children: [
      { key: 'permissions', label: 'Permissions',  icon: 'ti ti-key',          parentKey: 'builtin', url: '/admin/permissions' },
      { key: 'roles',       label: 'Rôles',        icon: 'ti ti-user-shield',   parentKey: 'builtin', url: '/admin/roles' },
      { key: 'users',       label: 'Utilisateurs', icon: 'ti ti-users',         parentKey: 'builtin', url: '/admin/users' },
      { key: 'menus',       label: 'Menus',        icon: 'ti ti-menu-2',        parentKey: 'builtin', url: '/admin/menus' },
      { key: 'i18n',        label: 'Traductions',  icon: 'ti ti-language',      parentKey: 'builtin', url: '/admin/translations' },
      { key: 'tenants',     label: 'Clients',      icon: 'ti ti-building-store',parentKey: 'builtin', url: '/admin/tenants' },
    ],
  },

  {
    key: 'modules',
    label: 'Modules Métier',
    icon: 'ti ti-apps',
    children: [
      { key: 'ddm-process', label: 'DDM Processus',     icon: 'ti ti-flow-branch',    parentKey: 'modules', url: '/modules/processus' },
      { key: 'ddm-risks',   label: 'DDM Risques',       icon: 'ti ti-alert-triangle', parentKey: 'modules', url: '/modules/risks' },
      { key: 'ddm-audit',   label: 'DDM Audit Interne', icon: 'ti ti-checklist',      parentKey: 'modules', url: '/modules/audit' },
    ],
  },

  {
    label: 'Dashboards', key: 'dashboards', isTitle: true, icon: 'ti ti-dashboard',
    children: [
      { url: '/',                    label: 'Sales',   icon: 'ti ti-dashboard',          parentKey: 'dashboards', badge: { variant: 'success', text: '5' } },
      { url: '/dashboards/clinic',   label: 'Clinic',  icon: 'ti ti-building-hospital',  parentKey: 'dashboards' },
      { url: '/dashboards/e-wallet', label: 'eWallet', icon: 'ti ti-wallet',             parentKey: 'dashboards', tooltip: { variant: 'danger', icon: 'ti ti-info-triangle', text: 'Your wallet balance is <b>low!</b>' } },
    ],
  },
]

/** ======================= API visibilité ======================= */
/** Shape renvoyée par l’API: { visibility: Record<menu_key, boolean> } */
export type MenuVisibility = Record<string, boolean>

export async function fetchMenuVisibility(): Promise<MenuVisibility> {
  try {
    const res = await fetch('/api/menu/visibility', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    if (!res.ok) return {}
    const data = await res.json()
    return (data?.visibility ?? {}) as MenuVisibility
  } catch {
    return {}
  }
}

/** ======================= Helpers ======================= */
function deepClone<T>(obj: T): T {
  return JSON.parse(JSON.stringify(obj))
}

function injectEntityChildren(base: MenuType[], entities: Array<{id:number|string; name:string}>) {
  const param = base.find(i => i.key === 'param')
  const chartsEntity = param?.children?.find(i => i.key === 'charts_entity')
  if (!chartsEntity) return
  chartsEntity.children = (entities || []).map(e => ({
    key: `entity_functions_${e.id}`,
    label: e.name,
    icon: 'ti ti-users',
    parentKey: 'charts_entity',
    url: `/param/charts/entity-functions/${e.id}`,
  }))
}

/** Filtre récursif selon visibility; si une clé n’est PAS dans visibility => public (on garde). */
function applyVisibility(items: MenuType[], visibility: MenuVisibility): MenuType[] {
  const allow = (key?: string) =>
    key ? (visibility.hasOwnProperty(key) ? !!visibility[key] : true) : true

  const walk = (nodes: MenuType[], parentKey?: string): MenuType[] =>
    (nodes || []).reduce<MenuType[]>((acc, node) => {
      if (!allow(node.key)) return acc
      const next: MenuType = { ...node }
      if (next.children?.length) {
        next.children = walk(next.children, next.key)
        // si isTitle et aucun enfant autorisé -> on masque le titre
        if (next.isTitle && (!next.children || next.children.length === 0)) return acc
      }
      acc.push(next)
      return acc
    }, [])

  return walk(items)
}

/** ======================= API principale côté front ======================= */
/**
 * Construit le menu final:
 *  - clone le fallback,
 *  - injecte les entités sous "charts_entity",
 *  - récupère la visibilité (permissions/roles) et filtre.
 */
export async function buildMenuWithEntities(entities: Array<{id:number|string; name:string}>) {
  const base = deepClone(menu)
  injectEntityChildren(base, entities)
  const visibility = await fetchMenuVisibility()
  return applyVisibility(base, visibility)
}

/** Legacy: met à jour seulement la section entité (sans permissions) */
export const updateMenuWithEntities = (entities: any[]): MenuType[] => {
  const updatedMenu = deepClone(menu)
  injectEntityChildren(updatedMenu, entities)
  return updatedMenu
}
