<!-- resources/js/layoutsparam/partials/components/VerticalMenu.vue -->
<template>
  <ul class="side-nav">
    <!-- Sections / items -->
    <template v-for="section in menuItems" :key="section.key || section.label">
      <!-- Titre -->
      <li v-if="section.isTitle" class="side-nav-title">
        {{ section.label }}
      </li>

      <!-- Divider -->
      <li v-else-if="section.isDivider" class="side-nav-divider"></li>

      <!-- Item avec enfants -->
      <li
        v-else-if="section.children && section.children.length > 0"
        class="side-nav-item"
        :class="{ active: isSectionActive(section) }"
      >
        <a class="side-nav-link" v-b-toggle="'collapse-' + section.key" role="button">
          <span class="menu-icon" v-if="section.icon">
            <i :class="section.icon"></i>
          </span>
          <span class="menu-text">{{ section.label }}</span>
          <span class="menu-arrow"></span>
        </a>

        <b-collapse :id="'collapse-' + section.key" :visible="isSectionActive(section)">
          <ul class="sub-menu">
            <li
              v-for="child in section.children"
              :key="child.key"
              class="side-nav-item"
              :class="{ active: isMenuItemActive(child) }"
            >
              <!-- Sous-élément avec enfants -->
              <template v-if="child.children && child.children.length > 0">
                <a class="side-nav-link" v-b-toggle="'collapse-' + child.key" role="button">
                  <span class="menu-text">{{ child.label }}</span>
                  <span class="menu-arrow"></span>
                </a>

                <b-collapse :id="'collapse-' + child.key" :visible="isMenuItemActive(child)">
                  <ul class="sub-menu">
                    <li
                      v-for="subChild in child.children"
                      :key="subChild.key"
                      class="side-nav-item"
                      :class="{ active: isMenuItemActive(subChild) }"
                    >
                      <Link
                        :href="subChild.url!"
                        :target="subChild.target"
                        class="side-nav-link"
                        :class="{ active: isMenuItemActive(subChild) }"
                      >
                        <span class="menu-text">{{ subChild.label }}</span>
                      </Link>
                    </li>
                  </ul>
                </b-collapse>
              </template>

              <!-- Sous-élément simple -->
              <template v-else>
                <Link
                  :href="child.url!"
                  :target="child.target"
                  class="side-nav-link"
                  :class="{ active: isMenuItemActive(child) }"
                >
                  <span class="menu-text">{{ child.label }}</span>
                </Link>
              </template>
            </li>
          </ul>
        </b-collapse>
      </li>

      <!-- Item simple -->
      <li v-else class="side-nav-item" :class="{ active: isMenuItemActive(section) }">
        <Link
          :href="section.url!"
          :target="section.target"
          class="side-nav-link"
          :class="{ active: isMenuItemActive(section) }"
        >
          <span class="menu-icon" v-if="section.icon">
            <i :class="section.icon"></i>
          </span>
          <span class="menu-text">{{ section.label }}</span>

          <span
            v-if="section.badge"
            class="badge"
            :class="`badge-${section.badge.variant || 'primary'}`"
          >
            {{ section.badge.text }}
          </span>
        </Link>
      </li>
    </template>

    <!-- État entités -->
    <li v-if="loadingEntities" class="side-nav-item">
      <a class="side-nav-link text-info">
        <span class="menu-icon"><i class="ti ti-loader animate-spin"></i></span>
        <span class="menu-text">Chargement des entités...</span>
      </a>
    </li>

    <li v-else-if="entitiesError" class="side-nav-item">
      <a class="side-nav-link text-danger">
        <span class="menu-icon"><i class="ti ti-alert-triangle"></i></span>
        <span class="menu-text">Erreur chargement entités</span>
      </a>
    </li>
  </ul>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { MenuType } from '@/types/layout'

/* ==========
 * PROPS
 * ========== */
const props = defineProps<{
  /** Menu déjà prêt (facultatif). Si fourni, on l’utilise tel quel. */
  items?: MenuType[]
  /** Code du module à charger (ex: 'param.projects'). */
  moduleCode?: string | null
}>()

/* ==========
 * STATES
 * ========== */
const page = usePage()
const currentUrl = computed(() => page.url)

const menuItems = ref<MenuType[]>(props.items ?? [])
const loadingMenu = ref(true)
const menuError = ref(false)

const entitiesData = ref<any[]>([])
const loadingEntities = ref(false)
const entitiesError = ref(false)

/* ==========
 * HELPERS (HTTP)
 * ========== */
type ServerMenuNode = {
  id: number
  key: string
  label: string
  type?: 'item' | 'title' | 'divider' | null
  icon?: string | null
  url?: string | null
  route_name?: string | null
  target?: string | null
  sort?: number | null
  children?: ServerMenuNode[]
}

const isSuperAdmin = computed<boolean>(() => {
  const u: any = (page.props as any)?.auth?.user
  return !!(u?.is_global_admin || u?.roles?.some((r: any) => (r.name || r)?.toString().toLowerCase().includes('super')))
})

async function fetchMenuStructure(moduleCode?: string | null): Promise<ServerMenuNode[]> {
  const qs = moduleCode && !isSuperAdmin.value ? `?module=${encodeURIComponent(moduleCode)}` : ''
  const res = await fetch(`/api/menu/structure${qs}`, {
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin',
  })
  if (!res.ok) return []
  const data = await res.json()
  return (Array.isArray(data) ? data : (data?.data ?? [])) as ServerMenuNode[]
}

async function fetchEntities(): Promise<any[]> {
  const res = await fetch('/api/menu/entities', {
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin',
  })
  if (!res.ok) return []
  const data = await res.json()
  return data?.entities ?? data ?? []
}

/* ==========
 * MAPPERS
 * ========== */
function mapServerToClient(node: ServerMenuNode, parentKey?: string): MenuType {
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
      .map((c) => mapServerToClient(c, node.key))
  }

  return item
}

/* ==========
 * ENTITIES INJECTION (si la section existe)
 * ========== */
function injectEntities(items: MenuType[], entities: any[] = []) {
  const param = items.find((i) => i.key === 'param')
  const chartsEntity = param?.children?.find((i) => i.key === 'charts_entity')
  if (!chartsEntity) return

  chartsEntity.children = []

  if (entities.length) {
    chartsEntity.children = entities.map((e, idx) => ({
      key: `entity_functions_${e.id}`,
      label: e.name,
      icon: 'ti ti-building-skyscraper',
      parentKey: 'charts_entity',
      url: `/param/charts/entity-functions/${e.id}`,
      routes: ['param.charts.entity.functions.show'],
      sort: idx,
    }))
    chartsEntity.children.push({
      key: 'charts_entity_divider',
      label: '',
      parentKey: 'charts_entity',
      isDivider: true,
    })
  }

  chartsEntity.children.push({
    key: 'entity_functions_all',
    label: 'Toutes les entités',
    icon: 'ti ti-chart-infographic',
    parentKey: 'charts_entity',
    url: '/param/charts/entity-functions',
    routes: ['param.charts.entity.functions.index'],
    badge: entities.length ? { variant: 'info', text: String(entities.length) } : undefined,
  })
}

/* ==========
 * LOADERS
 * ========== */
async function loadEntities() {
  try {
    loadingEntities.value = true
    entitiesError.value = false
    entitiesData.value = await fetchEntities()
  } catch {
    entitiesError.value = true
    entitiesData.value = []
  } finally {
    loadingEntities.value = false
  }
}

async function loadMenu() {
  if (props.items && props.items.length) {
    // Si items est passé, on n’appelle pas l’API
    menuItems.value = props.items
    return
  }

  try {
    loadingMenu.value = true
    menuError.value = false

    const serverTree = await fetchMenuStructure(props.moduleCode ?? null)
    let base = serverTree
      .sort((a, b) => (a.sort ?? 0) - (b.sort ?? 0))
      .map((n) => mapServerToClient(n))

    // Injection entités (si la section existe)
    injectEntities(base, entitiesData.value)

    menuItems.value = base
  } catch (e) {
    console.error('❌ Erreur chargement menu:', e)
    menuError.value = true
    menuItems.value = []
  } finally {
    loadingMenu.value = false
  }
}

/* ==========
 * ACTIVE HELPERS
 * ========== */
const isMenuItemActive = (menuItem: MenuType): boolean => {
  if (!menuItem.url) return false
  if (menuItem.url === currentUrl.value) return true
  // fallback par préfixe (utile si params/segments)
  return currentUrl.value.startsWith(menuItem.url)
}

const isSectionActive = (section: MenuType): boolean => {
  if (!section.children) return false
  return section.children.some((child) => {
    if (child.children?.length) {
      return child.children.some((sc) => isMenuItemActive(sc))
    }
    return isMenuItemActive(child)
  })
}

/* ==========
 * MOUNT & WATCH
 * ========== */
onMounted(async () => {
  await loadEntities()
  await loadMenu()
})

watch(
  () => props.moduleCode,
  async () => {
    await loadMenu()
  }
)

watch(
  () => props.items,
  async (val) => {
    if (val && val.length) {
      menuItems.value = val
    } else {
      await loadMenu()
    }
  },
  { deep: true }
)
</script>

<style scoped>
.animate-spin { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

.side-nav-link.active {
  background-color: rgba(var(--bs-primary-rgb), 0.1);
  color: var(--bs-primary) !important;
  font-weight: 600;
}
.badge { margin-left: auto; font-size: 0.7em; }
</style>
