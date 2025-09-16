<template>
    <label v-if="label" :for="id" class="form-label" :class="labelClass">{{ label }}</label>
    <b-form-input :type="type ?? 'text'" :id="id" :placeholder="placeholder" :value="modelValue" @input="updateValue" v-bind="$attrs" />
</template>

<script setup lang="ts">
import flatpickr from 'flatpickr';

import type { InputType } from 'bootstrap-vue-next';
import { onMounted } from 'vue';

type PropsType = {
    type?: InputType;
    label?: string;
    labelClass?: string;
    placeholder?: string;
    id: string;
    modelValue?: string;
    options?: object;
};

const props = defineProps<PropsType>();

const emit = defineEmits(['update:modelValue']);

const updateValue = (e: Event) => {
    emit('update:modelValue', (e.target as HTMLInputElement).value);
};

onMounted(() => {
    const ele = document.getElementById(props.id);
    if (ele) {
        flatpickr(ele, { ...props.options, defaultDate: props.modelValue });
    }
});
</script>
