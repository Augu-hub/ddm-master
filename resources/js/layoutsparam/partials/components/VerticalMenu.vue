<template>
  <ul class="side-nav">
    <template v-for="section in (menu || [])" :key="section.key || section.label">
      <li v-if="section.isTitle" class="side-nav-title">{{ section.label }}</li>

      <template v-else-if="section.children">
        <template v-for="child in section.children" :key="child.key">
          <!-- child avec enfants -->
          <li v-if="child.children" class="side-nav-item" :class="{ active: parent && child.key === parent.key }">
            <a class="side-nav-link" v-b-toggle="child.key" role="button">
              <span class="menu-icon"><i :class="child.icon"></i></span>
              <span class="menu-text">{{ child.label }}</span>
              <span class="menu-arrow"></span>
            </a>

            <!-- IMPORTANT: plus de parent!.key -->
            <b-collapse :id="child.key" :visible="child.key === parent?.key">
              <ul class="sub-menu">
                <li v-for="sub in child.children" :key="sub.key" class="side-nav-item" :class="{ active: sub.url === currentUrl }">
                  <Link :href="sub.url!" :target="sub.target" class="side-nav-link" :class="{ active: sub.url === currentUrl }">
                    <span class="menu-text">{{ sub.label }}</span>

                    <b-badge v-if="sub.badge" :variant="null" pill class="float-end rounded" :class="`bg-${sub.badge.variant}`">
                      {{ sub.badge.text }}
                    </b-badge>

                    <b-badge v-if="sub.tooltip" :variant="null" class="menu-alert fs-16 p-0" :class="`text-${sub.tooltip.variant}`">
                      <i :class="sub.tooltip.icon" v-b-tooltip="sub.tooltip.text"></i>
                    </b-badge>
                  </Link>
                </li>
              </ul>
            </b-collapse>
          </li>

          <!-- child sans enfants -->
          <li v-else class="side-nav-item" :class="{ active: child.url === currentUrl }">
            <Link :href="child.url!" :target="child.target" class="side-nav-link" :class="{ active: child.url === currentUrl }">
              <span class="menu-icon"><i :class="child.icon"></i></span>
              <span class="menu-text">{{ child.label }}</span>

              <b-badge v-if="child.badge" :variant="null" pill class="float-end rounded" :class="`bg-${child.badge.variant}`">
                {{ child.badge.text }}
              </b-badge>

              <b-badge v-if="child.tooltip" :variant="null" class="menu-alert fs-16 p-0" :class="`text-${child.tooltip.variant}`">
                <i :class="child.tooltip.icon" v-b-tooltip="child.tooltip.text"></i>
              </b-badge>
            </Link>
          </li>
        </template>
      </template>
    </template>
  </ul>
</template>

<script setup lang="ts">
import { menu } from '@/helpers/menu'
// Vérifie bien le chemin :
import { getActiveItem, getParentOfActiveItem } from '@/layouts/partials/components/menu'
import type { MenuType } from '@/types/layout'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const currentUrl = page.url

const active: MenuType | null = getActiveItem(currentUrl)
const parent: MenuType | null = active?.parentKey
  ? getParentOfActiveItem(active.parentKey)
  : null
</script>
