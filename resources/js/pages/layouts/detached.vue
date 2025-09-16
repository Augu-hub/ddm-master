<template>
    <VerticalLayout>
        <PageTitle title="Detached" subtitle="Layouts" />
    </VerticalLayout>
</template>

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { useLayoutStore } from '@/stores/layout';
import { onMounted, onUnmounted } from 'vue';

const { setLayoutMode } = useLayoutStore();

onMounted(() => {
    setLayoutMode('detached');
});

onUnmounted(() => {
    setLayoutMode('fluid');
});
</script>
