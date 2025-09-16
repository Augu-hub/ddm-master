<template>
    <b-card no-body class="border-light border">
        <b-card-body>
            <div class="d-flex align-items-start justify-content-between gap-2">
                <div
                    class="avatar-md d-inline-flex align-items-center justify-content-center rounded-2 flex-shrink-0"
                    :class="`bg-${app.variant}-subtle`"
                >
                    <Icon :icon="app.icon" class="fs-18" />
                </div>
                <div class="d-flex align-items-center gap-1">
                    <i v-if="app.isStared" class="ti ti-star-filled text-warning fs-16"></i>
                    <i v-else class="ti ti-star-filled text-muted fs-16"></i>

                    <b-dropdown :variant="null" no-caret class="text-muted flex-shrink-0" toggle-class="fs-20 link-reset p-0">
                        <template v-slot:button-content>
                            <i class="ti ti-dots-vertical"></i>
                        </template>

                        <b-dropdown-item><i class="ti ti-share me-1"></i>Share</b-dropdown-item>
                        <b-dropdown-item><i class="ti ti-link me-1"></i> Get Sharable Link</b-dropdown-item>
                        <b-dropdown-item><i class="ti ti-download me-1"></i> Download</b-dropdown-item>
                        <b-dropdown-item><i class="ti ti-pin me-1"></i> Pin</b-dropdown-item>
                        <b-dropdown-item><i class="ti ti-edit me-1"></i> Edit</b-dropdown-item>
                        <b-dropdown-item><i class="ti ti-trash me-1"></i> Delete</b-dropdown-item>
                    </b-dropdown>
                </div>
            </div>

            <div class="flex-grow-1 mt-3">
                <h5 class="mb-1">
                    <a href="#!" class="link-reset">{{ app.name }}</a>
                </h5>
                <p class="text-muted mb-0">{{ app.totalFiles }} Files</p>
            </div>
            <div class="d-flex align-items-center justify-content-between mb-1 mt-3">
                <p class="fs-14 mb-0">{{ app.usedSpace }}GB</p>
                <p class="fs-14 mb-0">{{ app.totalSpace }}GB</p>
            </div>

            <b-progress size="sm" :value="app.usedSpace" :max="app.totalSpace" striped animated :variant="app.variant" height="5px" />
        </b-card-body>
    </b-card>
</template>

<script setup lang="ts">
import type { AppType } from '@/pages/apps/file-manager/components/types';
import { Icon } from '@iconify/vue';
import type { PropType } from 'vue';

defineProps({
    app: {
        type: Object as PropType<AppType>,
        required: true,
    },
});
</script>
