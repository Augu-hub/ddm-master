<template>
    <b-card no-body>
        <b-card-body>
            <div class="d-flex align-items-start gap-3">
                <div v-if="item.icon">
                    <div class="avatar-lg bg-primary d-flex justify-content-center rounded bg-opacity-10">
                        <Icon :icon="item.icon" class="fs-32 text-primary align-self-center"></Icon>
                    </div>
                </div>

                <div>
                    <b-card-title tag="h4" class="d-flex align-items-center mb-1 gap-2">{{ item.label }}</b-card-title>
                    <p class="text-primary fw-medium fs-20 mb-0">
                        {{ item.prefix }}{{ item.value }}{{ item.suffix }}
                        <span v-if="item.duration" class="fs-15 text-muted ms-1">
                            {{ toSentenceCase(item.duration) }}
                        </span>
                    </p>
                </div>

                <b-dropdown :variant="null" no-caret toggle-class="text-muted card-drop p-0" class="ms-auto">
                    <template v-slot:button-content>
                        <i class="ti ti-dots-vertical"></i>
                    </template>
                    <b-dropdown-item>Action</b-dropdown-item>
                    <b-dropdown-item>Another Action</b-dropdown-item>
                    <b-dropdown-item>Something else here</b-dropdown-item>
                </b-dropdown>
            </div>
            <div v-if="item.subStatistic" class="mt-3">
                <p
                    v-for="(child, idx) in item.subStatistic"
                    class="text-dark fs-14 d-flex justify-content-between"
                    :class="idx != item.subStatistic.length - 1 ? 'mb-1' : 'mb-0'"
                >
                    {{ child.label }}
                    <span class="text-dark fw-semibold">{{ child.prefix }} {{ child.value }} {{ child.suffix }}</span>
                </p>
            </div>
        </b-card-body>
        <b-card-footer class="border-top border-dashed">
            <a href="#" class="link-primary fw-medium">View More <i class="ti ti-arrow-right"></i></a>
        </b-card-footer>
    </b-card>
</template>

<script setup lang="ts">
import { toSentenceCase } from '@/helpers/change-casing';
import type { StatisticType } from '@/pages/hospital/appointments/components/data';
import { Icon } from '@iconify/vue';
import type { PropType } from 'vue';

defineProps({
    item: {
        type: Object as PropType<StatisticType>,
        required: true,
    },
});
</script>
