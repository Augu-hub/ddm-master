<template>
    <label v-if="label" :for="id" class="form-label" :class="labelClass">{{ label }}</label>
    <select :id="id" :value="modelValue" @change="updateValue" v-bind="$attrs" class="form-select">
        <slot />
        <template v-if="options">
            <option v-for="(option, idx) in options" :key="idx" :value="option.value">
                {{ option.text }}
            </option>
        </template>
    </select>
</template>

<script setup lang="ts">
import Choices, { type Options } from 'choices.js';
import { onMounted } from 'vue';

type PropsType = {
    id: string;
    label?: string;
    labelClass?: string;
    modelValue?: string;
    options?: { value: string | number; text: string }[];
    choiceOptions?: Options;
};
const props = defineProps<PropsType>();

const emit = defineEmits(['update:modelValue']);

const updateValue = (e: Event) => {
    emit('update:modelValue', (e.target as HTMLSelectElement).value);
};

onMounted(() => {
    const choices = new Choices(`#${props.id}`, {
        ...props.choiceOptions,
    });
});
</script>
