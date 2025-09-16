<template>
    <ul class="navbar-nav">
        <DropDown is="li" v-for="item in menu" :key="item.key" class="nav-item hover-dropdown">
            <a
                class="nav-link dropdown-toggle drop-arrow-none"
                href="#"
                :id="item.url"
                data-bs-toggle="dropdown"
                role="button"
                aria-haspopup="true"
                aria-expanded="false"
            >
                <span class="menu-icon"><i :class="item.icon"></i></span>
                <span class="menu-text"> {{ item.label }} </span>
                <div class="menu-arrow"></div>
            </a>

            <div class="dropdown-menu" :aria-labelledby="item.url">
                <template v-for="(child, idx) in item.children" :key="idx">
                    <!--two level nested-->
                    <template v-if="child.children">
                        <DropDown class="hover-dropdown">
                            <a
                                class="dropdown-item dropdown-toggle drop-arrow-none"
                                :class="{ active: parent && child.key === parent.key }"
                                href="#"
                                :id="child.url"
                                role="button"
                                data-bs-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false"
                            >
                                {{ child.label }}
                                <div class="menu-arrow"></div>
                            </a>
                            <div class="dropdown-menu" :class="{ 'dropdown-menu-md': child.children.length > 12 }" :aria-labelledby="child.url">
                                <b-row v-if="child.children.length > 12">
                                    <b-col cols="6" v-for="(items, idx) in chunkArray(child.children, 2)" :key="idx">
                                        <template v-for="(item, idx) in items" :key="idx">
                                            <Link
                                                :href="item.url!"
                                                :target="item.target"
                                                :class="{ active: item.url === currentUrl }"
                                                class="dropdown-item"
                                                >{{ item.label }}
                                            </Link>
                                        </template>
                                    </b-col>
                                </b-row>

                                <template v-else>
                                    <template v-for="(item, idx) in child.children" :key="idx">
                                        <Link
                                            :href="item.url!"
                                            :target="item.target"
                                            :class="{ active: item.url === currentUrl }"
                                            class="dropdown-item"
                                            >{{ item.label }}
                                        </Link>
                                    </template>
                                </template>
                            </div>
                        </DropDown>
                    </template>

                    <!--one level nested-->
                    <template v-else>
                        <Link :href="child.url!" :target="child.target" :class="{ active: child.url === currentUrl }" class="dropdown-item">
                            {{ child.label }}
                        </Link>
                    </template>
                </template>
            </div>
        </DropDown>
    </ul>
</template>

<script setup lang="ts">
import DropDown from '@/components/DropDown.vue';
import { chunkArray } from '@/helpers/array';
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
