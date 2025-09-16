<template>
    <b-form-input type="text" :id="id" v-bind="$attrs" />
</template>

<script setup lang="ts">
import Inputmask from 'inputmask';
import { onMounted } from 'vue';

type PropsType = {
    is?: string;
    id: string;
    mask: string;
    options?: object;
};

const props = defineProps<PropsType>();

onMounted(() => {
    new Inputmask({ mask: props.mask, ...props.options }).mask(`#${props.id}`);
});
</script>
