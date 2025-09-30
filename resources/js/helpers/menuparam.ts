// resources/js/helpers/menu.ts
import type { MenuType } from '@/types/layout'

export const menu: MenuType[] = [
  // ===== SEEDER EN HAUT =====
  {
    label: 'DIADDEM',
    key: 'seed-root',
    isTitle: true,
    icon: 'ti ti-database-cog',
  },

  // Dashboard (ton '/' pointe vers dashboards/sales/index)
  {
    key: 'dashboard',
    label: 'Tableau de bord',
    icon: 'ti ti-layout-dashboard',
    url: '/', // <-- URL directe
  },

  // PARAM (base DIADDEM)
  {
    key: 'param',
    label: 'Paramétrage',
    icon: 'ti ti-adjustments',
    children: [
      { key: 'project',      label: 'Projets',        icon: 'ti ti-folders',             parentKey: 'param', url: '/param/projects' },
      { key: 'entity',       label: 'Entités',        icon: 'ti ti-building-community',  parentKey: 'param', url: '/param/entities' },
      { key: 'process',      label: 'Macro-Processus-Activité',            icon: 'ti ti-sitemap',             parentKey: 'param', url: '/param/process' },
      { key: 'function',     label: 'Fonctions',      icon: 'ti ti-components',          parentKey: 'param', url: '/param/function' },
      
      {
        key: 'charts',
        label: 'Organigrammes Projet',
        icon: 'ti ti-chart-infographic',
        parentKey: 'param',
        children: [
          { key: 'entity_chart',        label: 'Organigramme Entités',    icon: 'ti ti-building-skyscraper', parentKey: 'charts', url: '/param/charts/entity' },
          { key: 'function_chart',      label: 'Organigramme Fonctions',  icon: 'ti ti-hierarchy',           parentKey: 'charts', url: '/param/charts/function' },
          { key: 'distribution_chart',  label: 'Organigramme Répartition',icon: 'ti ti-chart-arrows',        parentKey: 'charts', url: '/param/charts/distribution' },
        ],
      },
     { key: 'distribution', label: 'Répartition MPA/fonction', icon: 'ti ti-arrows-shuffle', parentKey: 'param', url: '/param/distribution' },

   // resources/js/helpers/menu.ts (extrait)
// resources/js/helpers/menu.ts
{
  key: 'charts_entity',
  label: 'Organigrammes Entité',
  icon: 'ti ti-chart-infographic',
  parentKey: 'param',
  children: [], // rempli dynamiquement
},





    ],
  },

  // ADMINISTRATION (builtin)
  {
    key: 'builtin',
    label: 'Administration',
    icon: 'ti ti-settings',
    children: [
      { key: 'permissions', label: 'Permissions',   icon: 'ti ti-key',           parentKey: 'builtin', url: '/admin/permissions' },
      { key: 'roles',       label: 'Rôles',         icon: 'ti ti-user-shield',   parentKey: 'builtin', url: '/admin/roles' },
      { key: 'users',       label: 'Utilisateurs',  icon: 'ti ti-users',         parentKey: 'builtin', url: '/admin/users' },
      { key: 'menus',       label: 'Menus',         icon: 'ti ti-menu-2',        parentKey: 'builtin', url: '/admin/menus' },
      { key: 'i18n',        label: 'Traductions',   icon: 'ti ti-language',      parentKey: 'builtin', url: '/admin/translations' },
      { key: 'tenants',     label: 'Clients',       icon: 'ti ti-building-store',parentKey: 'builtin', url: '/admin/tenants' },
    ],
  },

  // MODULES MÉTIER
  {
    key: 'modules',
    label: 'Modules Métier',
    icon: 'ti ti-apps',
    children: [
      { key: 'ddm-process', label: 'DDM Processus',      icon: 'ti ti-flow-branch',     parentKey: 'modules', url: '/modules/processus' },
      { key: 'ddm-risks',   label: 'DDM Risques',        icon: 'ti ti-alert-triangle',  parentKey: 'modules', url: '/modules/risks' },
      { key: 'ddm-audit',   label: 'DDM Audit Interne',  icon: 'ti ti-checklist',       parentKey: 'modules', url: '/modules/audit' },
    ],
  },

  // SUPPORT & AIDE
  {
    key: 'support',
    label: 'Support & Aide',
    icon: 'ti ti-lifebuoy',
    children: [
      { key: 'documentation', label: 'Documentation',  icon: 'ti ti-book',            parentKey: 'support', url: '/support/documentation' },
      { key: 'help-center',   label: "Centre d'aide",  icon: 'ti ti-help',            parentKey: 'support', url: '/support/help' },
      { key: 'feedback',      label: 'Feedback',       icon: 'ti ti-message-circle',  parentKey: 'support', url: '/support/feedback' },
    ],
  },

  // ===== TES BLOCS DU TEMPLATE (si tu veux les garder après) =====
  {
    label: 'Dashboards',
    key: 'dashboards',
    isTitle: true,
    icon: 'ti ti-dashboard',
    children: [
      { url: '/',                   label: 'Sales',   icon: 'ti ti-dashboard',           parentKey: 'dashboards', badge: { variant: 'success', text: '5' } },
      { url: '/dashboards/clinic',  label: 'Clinic',  icon: 'ti ti-building-hospital',   parentKey: 'dashboards' },
      { url: '/dashboards/e-wallet',label: 'eWallet', icon: 'ti ti-wallet',              parentKey: 'dashboards', tooltip: { variant: 'danger', icon: 'ti ti-info-triangle', text: 'Your wallet balance is <b>low!</b>' } },
    ],
  },
  // … (Apps / Pages / Components / More) conserve tes blocs actuels tels quels
]
