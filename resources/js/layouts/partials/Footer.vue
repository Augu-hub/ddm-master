<template>
    <footer class="footer">
        <div class="page-container">
            <b-row class="row">
                <b-col md="6" class="text-md-start text-center">
                    {{ currentYear }}
                    © {{ appName }} - By <span class="fw-bold text-decoration-underline text-uppercase text-reset fs-12">{{ author }}</span>
                </b-col>
                <b-col md="6">
                    <div class="text-md-end footer-links d-none d-md-block">
                        <Link v-for="item in footerItems" :key="item.label" :href="item.url">{{ item.label }} </Link>
                    </div>
                </b-col>
            </b-row>
        </div>
    </footer>
</template>

<script setup lang="ts">
import { appName, author, currentYear } from '@/helpers';
import { footerItems } from '@/layouts/partials/data';
import { Link } from '@inertiajs/vue3';
</script>
