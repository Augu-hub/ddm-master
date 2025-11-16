// resources/js/helpers/menu.ts
import type { MenuType } from '@/types/layout'

export interface MenuBadge { variant: 'primary'|'secondary'|'success'|'danger'|'warning'|'info'|'light'|'dark'; text: string }
export interface MenuTooltip { variant: 'primary'|'secondary'|'success'|'danger'|'warning'|'info'|'light'|'dark'; icon: string; text: string }
export interface Entity { id: number|string; name: string; slug?: string }

type ServerMenuNode = {
  id: number; key: string; label: string;
  type?: 'item'|'title'|'divider'|null; icon?: string|null;
  url?: string|null; route_name?: string|null; target?: string|null;
  sort?: number|null; badge_json?: MenuBadge|null; tooltip_json?: MenuTooltip|null;
  meta_json?: Record<string, unknown>|null; children?: ServerMenuNode[];
}

export const menu: MenuType[] = [
  { label: 'DIADDEM', key: 'seed-root', isTitle: true, icon: 'ti ti-database-cog' },
  { key: 'dashboard', label: 'Tableau de bord', icon: 'ti ti-layout-dashboard', url: '/', routes: ['dashboard'] },
  { key: 'modules', label: 'Modules Métier', icon: 'ti ti-apps', routes: [], children: [] },
];

function deepClone<T>(x:T):T { return JSON.parse(JSON.stringify(x)) }

export type MenuVisibility = Record<string, boolean>

function toQuery(params: Record<string, string|undefined>) {
  const q = new URLSearchParams();
  Object.entries(params).forEach(([k,v]) => { if (v) q.set(k, v) });
  const s = q.toString();
  return s ? `?${s}` : '';
}

export async function fetchMenuVisibility(moduleCode?: string, serviceCode?: string): Promise<MenuVisibility> {
  try {
    const res = await fetch(`/api/menu/visibility${toQuery({module: moduleCode, service: serviceCode})}`, {
      headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' }, credentials:'same-origin'
    });
    if (!res.ok) return {};
    const data = await res.json();
    return (data?.visibility ?? {}) as MenuVisibility;
  } catch { return {} }
}

async function fetchMenuStructure(moduleCode?: string, serviceCode?: string): Promise<ServerMenuNode[]> {
  try {
    const res = await fetch(`/api/menu/structure${toQuery({module: moduleCode, service: serviceCode})}`, {
      headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' }, credentials:'same-origin'
    });
    if (!res.ok) return [];
    const data = await res.json();
    return (Array.isArray(data) ? data : (data?.data ?? [])) as ServerMenuNode[];
  } catch { return [] }
}

export type DbModule = { code:string; name:string; entry_route?:string|null; service?:{name?:string|null}|null }
export async function fetchMyModules(): Promise<DbModule[]> {
  try {
    const res = await fetch('/api/me/modules', {
      headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' }, credentials:'same-origin'
    });
    if (!res.ok) return [];
    const data = await res.json();
    return Array.isArray(data) ? (data as DbModule[]) : ((data?.modules ?? []) as DbModule[]);
  } catch { return [] }
}

export async function fetchEntities(): Promise<Entity[]> {
  try {
    const res = await fetch('/api/menu/entities', {
      headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' }, credentials:'same-origin'
    });
    if (!res.ok) return [];
    const data = await res.json();
    return (data?.entities ?? data ?? []) as Entity[];
  } catch { return [] }
}

function serverToClient(node: ServerMenuNode, parentKey?: string): MenuType {
  const isTitle   = node.type === 'title';
  const isDivider = node.type === 'divider';
  const routes    = node.route_name ? [node.route_name] : [];

  const item: MenuType = {
    key: node.key, label: node.label, icon: node.icon ?? undefined,
    isTitle, isDivider, url: node.url ?? undefined, routes, parentKey,
    badge: node.badge_json ?? undefined, tooltip: node.tooltip_json ?? undefined, sort: node.sort ?? undefined,
  };

  if (node.children?.length) {
    item.children = node.children
      .sort((a,b) => (a.sort ?? 0) - (b.sort ?? 0))
      .map(c => serverToClient(c, node.key));
  }
  return item;
}

const MODULE_STYLE: Record<string, { icon: string }> = {
  'param.projects': { icon: 'ti ti-adjustments' },
  'process.core'  : { icon: 'ti ti-flow-branch' },
  'risk.core'     : { icon: 'ti ti-alert-triangle' },
  'audit.core'    : { icon: 'ti ti-checklist' },
};

function ensureModulesSection(base: MenuType[]): MenuType {
  let section = base.find(i => i.key === 'modules');
  if (!section) {
    section = { key: 'modules', label: 'Modules Métier', icon: 'ti ti-apps', routes: [], children: [] };
    base.push(section);
  }
  section.children ??= [];
  return section;
}

function injectModulesFromDb(base: MenuType[], modules: DbModule[] = []) {
  const section = ensureModulesSection(base);
  section.children = [];
  modules.forEach((m, i) => {
    const icon = MODULE_STYLE[m.code]?.icon ?? 'ti ti-apps';
    const url  = `/m/${m.code}?go=1`;
    section.children!.push({
      key: `mod_${m.code}`, label: m.name, icon, parentKey:'modules', url, routes:[], sort:i
    } as MenuType);
  });
}

function applyVisibility(items: MenuType[], visibility: MenuVisibility): MenuType[] {
  const allow = (key?: string) => key ? (visibility.hasOwnProperty(key) ? !!visibility[key] : true) : true;

  const walk = (nodes: MenuType[]): MenuType[] =>
    (nodes || []).reduce<MenuType[]>((acc, node) => {
      if (!allow(node.key)) return acc;
      const next: MenuType = { ...node };
      if (next.children?.length) {
        next.children = walk(next.children);
        if (next.isTitle && (!next.children || next.children.length === 0)) return acc;
        if (!next.isTitle && (!next.children || next.children.length === 0)) return acc;
      }
      acc.push(next); return acc;
    }, []);

  return walk(items);
}

/** === Builder “par module” === */
export async function buildMenuForModule(
  moduleCode: string,
  options?: { serviceCode?: string; includeModulesSection?: boolean; entities?: Entity[]; user?: any }
): Promise<MenuType[]> {
  const serviceCode = options?.serviceCode;
  const serverTree  = await fetchMenuStructure(moduleCode, serviceCode);
  let base: MenuType[] = serverTree.length
    ? serverTree.sort((a,b)=>(a.sort??0)-(b.sort??0)).map(n => serverToClient(n))
    : deepClone(menu);

  // (facultatif) entités dynamiques si ton module Param en a besoin
  const ents = options?.entities ?? await fetchEntities();
  // si tu veux conditionner aux seuls écrans Param, tu peux tester moduleCode ici
  // ex: if (moduleCode === 'param.projects') injectEntityChildren(base, ents);

  // (facultatif) la section “Modules Métier”
  if (options?.includeModulesSection !== false) {
    const mods = await fetchMyModules();
    injectModulesFromDb(base, mods);
  }

  const visibility = await fetchMenuVisibility(moduleCode, serviceCode);
  const visibleTree = applyVisibility(base, visibility);

  // (facultatif) petit filtre UI par user si besoin
  return options?.user ? visibleTree : visibleTree;
}

/** Compat : ancien builder global */
export async function buildMenuFromServer(entities?: Entity[], user?: any): Promise<MenuType[]> {
  const serverTree = await fetchMenuStructure();
  let base: MenuType[] = serverTree.length
    ? serverTree.sort((a,b)=>(a.sort??0)-(b.sort??0)).map(n => serverToClient(n))
    : deepClone(menu);

  const ents = entities ?? await fetchEntities();
  // injectEntityChildren(base, ents); // décommente si tu gardes les organigrammes dynamiques

  const mods = await fetchMyModules();
  injectModulesFromDb(base, mods);

  const visibility = await fetchMenuVisibility();
  const visibleTree = applyVisibility(base, visibility);

  return user ? visibleTree : visibleTree;
}

export function useMenu() {
  return {
    menu,
    buildMenuForModule,
    buildMenuFromServer,
    fetchMenuStructure,
    fetchMenuVisibility,
    fetchMyModules,
    fetchEntities,
  };
}

export default {
  menu,
  buildMenuForModule,
  buildMenuFromServer,
  fetchMenuStructure,
  fetchMenuVisibility,
  fetchMyModules,
  fetchEntities,
  useMenu,
};
