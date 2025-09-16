<template>
    <VerticalLayout>
        <PageTitle title="Fullscreen" subtitle="Layouts" />
    </VerticalLayout>
</template>

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { useLayoutStore } from '@/stores/layout';
import { onMounted, onUnmounted } from 'vue';

const { setLeftSideBarSize } = useLayoutStore();

onMounted(() => {
    setLeftSideBarSize('fullscreen');
});

onUnmounted(() => {
    setLeftSideBarSize('default');
});
</script>
