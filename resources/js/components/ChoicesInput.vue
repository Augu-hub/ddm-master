<template>
    <label v-if="label" :for="id" class="form-label text-muted" :class="labelClass">{{ label }}</label>
    <input :id="id" :type="type ?? 'text'" v-bind="$attrs" />
</template>

<script setup lang="ts">
import type { InputType } from 'bootstrap-vue-next';
import Choices from 'choices.js';
import { onMounted } from 'vue';

type PropsType = {
    id: string;
    type?: InputType;
    label?: string;
    labelClass?: string;
    placeholder?: string;
    choiceOptions?: object;
};

const props = defineProps<PropsType>();

onMounted(() => {
    new Choices(`#${props.id}`, { ...props.choiceOptions });
});
</script>
