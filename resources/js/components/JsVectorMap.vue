<template>
    <div :id="id" :style="{ width: '100%', height: height + 'px' }"></div>
</template>

<script setup lang="ts">
import '@/helpers/maps';
import 'jsvectormap';
import 'jsvectormap/dist/maps/world';
import 'jsvectormap/dist/maps/world-merc';
import { onMounted } from 'vue';

type PropsType = {
    id: string;
    height: number;
    options: object;
};
const props = defineProps<PropsType>();

onMounted(() => {
    new (window as any)['jsVectorMap'](props.options);
});
</script>
