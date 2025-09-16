<template>
    <component :is="is ?? 'div'" ref="dropdown" class="dropdown" :class="customClass" :id="id" v-bind="$attrs">
        <slot />
    </component>
</template>

<script setup lang="ts">
import { Dropdown } from 'bootstrap';
import { onMounted, ref } from 'vue';

type PropsType = {
    is?: string;
    id?: string;
    customClass?: string;
};

defineProps<PropsType>();

const dropdown = ref();

onMounted(() => {
    Dropdown.getOrCreateInstance(dropdown.value);
});
</script>
