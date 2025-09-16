<template>
    <div class="wrapper">
        <TopBar />
        <LeftSideBar />

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
import Customizer from '@/layoutsparam/partials/Customizer.vue';
import Footer from '@/layoutsparam/partials/Footer.vue';
import LeftSideBar from '@/layoutsparam/partials/LeftSideBar.vue';
import TopBar from '@/layoutsparam/partials/TopBar.vue';
</script>
