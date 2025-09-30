<!-- resources/js/components/nav/HydratedSidebar.vue -->
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { menu as staticMenu } from '@/helpers/menu'
import type { MenuType } from '@/types/layout'

// On clone le menu statique pour pouvoir le modifier
const fullMenu = ref<MenuType[]>(JSON.parse(JSON.stringify(staticMenu)))

function findByKey(list: MenuType[], key: string): MenuType | null {
  for (const item of list) {
    if (item.key === key) return item
    if (item.children?.length) {
      const f = findByKey(item.children, key)
      if (f) return f
    }
  }
  return null
}

function setChartsEntityChildren(children: MenuType[]) {
  const node = findByKey(fullMenu.value, 'charts_entity')
  if (!node) return
  node.children = children.length
    ? children
    : [{
        key: 'entity_none',
        label: 'Aucune entité trouvée',
        icon: 'ti ti-circle-dashed',
        parentKey: 'charts_entity',
        url: '#',
      }]
}

onMounted(async () => {
  try {
    const url = (window as any)?.route
      ? (window as any).route('param.entities.menu-children')
      : '/param/entities/menu-children'

    const res = await fetch(url, {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin',
    })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const data = await res.json()
    const children: MenuType[] = (Array.isArray(data) ? data : []).map((it: any) => ({
      key: it.key,
      label: it.label,
      icon: it.icon || 'ti ti-building-skyscraper',
      parentKey: 'charts_entity',
      url: it.url,
    }))
    setChartsEntityChildren(children)
  } catch (err) {
    console.error('NAV entities error:', err)
    setChartsEntityChildren([])
  }
})
</script>

<template>
  <!-- IMPORTANT : on passe le menu hydraté au composant Sidebar existant -->
  <Sidebar :items="fullMenu" />
</template>
