<template>
    <link rel="stylesheet"
      href="https://unpkg.com/@tabler/icons-webfont@2.47.0/tabler-icons.min.css" />

    <div class="wrapper">
        <TopBar />
        <NavBar />

        <div class="page-content">
            <div class="page-container">
                <slot />
            </div>

            <Footer />
            <Customizer />
        </div>
    </div>
</template>

<script setup lang="ts">
import { toggleDocumentAttribute } from '@/helpers/other';
import Customizer from '@/layouts/partials/Customizer.vue';
import Footer from '@/layouts/partials/Footer.vue';
import NavBar from '@/layouts/partials/NavBar.vue';
import TopBar from '@/layouts/partials/TopBar.vue';
import { onMounted, onUnmounted } from 'vue';

onMounted(() => {
    toggleDocumentAttribute('data-layout', 'topnav');
});

onUnmounted(() => {
    toggleDocumentAttribute('data-layout', 'topnav', 'html', true);
});
</script>
