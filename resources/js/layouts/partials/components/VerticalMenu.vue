<template>
    <ul class="side-nav">
        <template v-for="(item, idx) in menu || []" :key="idx">
            <li v-if="item.isTitle" class="side-nav-title">{{ item.label }}</li>

            <template v-if="item.children">
                <template v-for="(child, idx) in item.children" :key="idx">
                    <!--two level nested-->
                    <li v-if="child.children" class="side-nav-item" :class="{ active: parent && child.key === parent.key }">
                        <a class="side-nav-link" v-b-toggle="child.key" role="button">
                            <span class="menu-icon"><i :class="child.icon"></i></span>
                            <span class="menu-text"> {{ child.label }} </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <b-collapse :id="child.key" :visible="child.key === parent!.key">
                            <ul class="sub-menu">
                                <li
                                    v-for="(item, idx) in child.children"
                                    class="side-nav-item"
                                    :class="{ active: item.url === currentUrl }"
                                    :key="idx"
                                >
                                    <Link :href="item.url!" :target="item.target" class="side-nav-link" :class="{ active: item.url === currentUrl }">
                                        <span class="menu-text">{{ item.label }}</span>
                                        <b-badge :variant="null" pill v-if="item.badge" class="float-end rounded" :class="`bg-${item.badge.variant}`">
                                            {{ item.badge.text }}
                                        </b-badge>

                                        <b-badge
                                            :variant="null"
                                            v-if="item.tooltip"
                                            class="menu-alert fs-16 p-0"
                                            :class="`text-${item.tooltip.variant}`"
                                        >
                                            <i :class="item.tooltip.icon" v-b-tooltip="item.tooltip.text"></i>
                                        </b-badge>
                                    </Link>
                                </li>
                            </ul>
                        </b-collapse>
                    </li>

                    <!--one level nested-->
                    <li v-else class="side-nav-item" :class="{ active: child.url === currentUrl }">
                        <Link :href="child.url!" :target="child.target" class="side-nav-link" :class="{ active: child.url === currentUrl }">
                            <span class="menu-icon"><i :class="child.icon"></i></span>
                            <span class="menu-text"> {{ child.label }} </span>

                            <b-badge :variant="null" pill v-if="child.badge" class="float-end rounded" :class="`bg-${child.badge.variant}`">
                                {{ child.badge.text }}
                            </b-badge>

                            <b-badge :variant="null" v-if="child.tooltip" class="menu-alert fs-16 p-0" :class="`text-${child.tooltip.variant}`">
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
import { menu } from '@/helpers/menu';
import { getActiveItem, getParentOfActiveItem } from '@/layouts/partials/components/menu';
import { MenuType } from '@/types/layout';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const currentUrl = page.url;

const active: MenuType | null = getActiveItem(currentUrl);
let parent: MenuType | null;
if (active) {
    parent = getParentOfActiveItem(active.parentKey ?? '');
}
</script>
