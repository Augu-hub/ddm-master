<template>
    <div class="topbar-item d-none d-md-flex">
        <b-dropdown
            :variant="null"
            no-caret
            :offset="{ mainAxis: 25 }"
            toggle-class="p-0 m-0 topbar-link px-2 fw-medium"
            menu-class="dropdown-menu-xxl"
        >
            <template v-slot:button-content> Pages <i class="ti ti-chevron-down ms-1"></i> </template>

            <b-row class="g-0">
                <b-col md="4" v-for="(item, idx) in megaMenuItems" :key="idx" :class="`bg-${item.bgVariant} bg-opacity-50`">
                    <div class="p-3">
                        <h5 class="fw-semibold mb-2">{{ item.title }}</h5>
                        <ul class="list-unstyled megamenu-list">
                            <li v-for="(link, idx) in item.links" :key="idx">
                                <Link :href="link.url">{{ link.label }}</Link>
                            </li>
                        </ul>
                    </div>
                </b-col>
            </b-row>
        </b-dropdown>
    </div>
</template>

<script setup lang="ts">
import { megaMenuItems } from '@/layouts/partials/data';
import { Link } from '@inertiajs/vue3';
</script>
