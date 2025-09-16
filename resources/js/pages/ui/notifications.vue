<template>
    <VerticalLayout>
        <PageTitle title="Notification" subtitle="Base UI" />
        <b-row>
            <b-col cols="12">
                <UICard title="Bootstrap Toasts">
                    <b-row>
                        <b-col md="6">
                            <h5 class="mb-2">Basic</h5>
                            <p class="text-muted">
                                Toasts are as flexible as you need and have very little required markup. At a minimum, we require a single element to
                                contain your “toasted” content and strongly encourage a dismiss button.
                            </p>
                            <div class="p-3">
                                <b-toast :model-value="true" :noCloseButton="true">
                                    <template #title>
                                        <img :src="logoSm" alt="brand-logo" height="16" class="me-1" />
                                        <strong class="me-auto">Admin</strong>
                                        <small>11 mins ago</small>
                                        <button type="button" class="btn-close ms-2" data-bs-dismiss="toast" aria-label="Close"></button>
                                    </template>
                                    Hello, world! This is a toast message.
                                </b-toast>
                            </div>
                        </b-col>
                        <b-col md="6">
                            <h5 class="mb-2">Translucent</h5>
                            <p class="text-muted">
                                Toasts are slightly translucent, too, so they blend over whatever they might appear over. For browsers that support
                                the backdrop-filter CSS property, we’ll also attempt to blur the elements under a toast.
                            </p>

                            <div class="bg-light bg-opacity-50 p-3">
                                <b-toast :model-value="true" :noCloseButton="true">
                                    <template #title>
                                        <img :src="logoSm" alt="brand-logo" height="16" class="me-1" />
                                        <strong class="me-auto">Admin</strong>
                                        <small>11 mins ago</small>
                                        <button type="button" class="btn-close ms-2" data-bs-dismiss="toast" aria-label="Close"></button>
                                    </template>
                                    Hello, world! This is a toast message.
                                </b-toast>
                            </div>
                        </b-col>
                    </b-row>

                    <b-row>
                        <b-col md="6" class="mt-4">
                            <h5 class="mb-2">Stacking</h5>
                            <p class="text-muted">When you have multiple toasts, we default to vertiaclly stacking them in a readable manner.</p>
                            <div class="p-3">
                                <div aria-live="polite" aria-atomic="true" style="position: relative; min-height: 200px">
                                    <div class="toast-container" style="position: absolute; top: 0; right: 0">
                                        <b-toast :model-value="true" :noCloseButton="true">
                                            <template #title>
                                                <img :src="logoSm" alt="brand-logo" height="16" class="me-1" />
                                                <strong class="me-auto">Admin</strong>
                                                <small>11 mins ago</small>
                                                <button type="button" class="btn-close ms-2" data-bs-dismiss="toast" aria-label="Close"></button>
                                            </template>
                                            See? Just like this.
                                        </b-toast>

                                        <b-toast :model-value="true" :noCloseButton="true">
                                            <template #title>
                                                <img :src="logoSm" alt="brand-logo" height="16" class="me-1" />
                                                <strong class="me-auto">Admin</strong>
                                                <small>11 mins ago</small>
                                                <button type="button" class="btn-close ms-2" data-bs-dismiss="toast" aria-label="Close"></button>
                                            </template>
                                            Heads up, toasts will stack automatically
                                        </b-toast>
                                    </div>
                                </div>
                            </div>
                        </b-col>
                        <b-col md="6" class="mt-4">
                            <h5 class="mb-2">Placement</h5>
                            <p class="text-muted">
                                Place toasts with custom CSS as you need them. The top right is often used for notifications, as is the top middle. If
                                you’re only ever going to show one toast at a time, put the positioning styles right on the <code>.toast</code>.
                            </p>
                            <div class="p-3">
                                <div
                                    aria-live="polite"
                                    aria-atomic="true"
                                    class="d-flex justify-content-center align-items-center"
                                    style="min-height: 200px"
                                >
                                    <b-toast :model-value="true" :noCloseButton="true">
                                        <template #title>
                                            <img :src="logoSm" alt="brand-logo" height="16" class="me-1" />
                                            <strong class="me-auto">Admin</strong>
                                            <small>11 mins ago</small>
                                            <button type="button" class="btn-close ms-2" data-bs-dismiss="toast" aria-label="Close"></button>
                                        </template>
                                        Hello, world! This is a toast message.
                                    </b-toast>
                                </div>
                            </div>
                        </b-col>
                    </b-row>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col lg="6">
                <UICard title="Custom content">
                    <b-toast :model-value="true" :noCloseButton="true" class="mb-4">
                        <div class="d-flex justify-content-between">
                            <div class="toast-body m-0 p-0">Hello, world! This is a toast message.</div>
                            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </b-toast>

                    <b-toast :model-value="true" :noCloseButton="true" class="bg-primary mb-4 border-0 text-white">
                        <div class="d-flex justify-content-between">
                            <div class="toast-body m-0 p-0">Hello, world! This is a toast message.</div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </b-toast>

                    <b-toast :model-value="true" :noCloseButton="true" class="mb-4">
                        Hello, world! This is a toast message.
                        <div class="border-top mt-2 pt-2">
                            <b-button type="button" variant="primary" size="sm">Take action</b-button> {{ ' ' }}
                            <b-button type="button" variant="secondary" size="sm" data-bs-dismiss="toast">Close</b-button>
                        </div>
                    </b-toast>

                    <b-toast :model-value="true" :noCloseButton="true" class="bg-primary" body-class="text-white">
                        Hello, world! This is a toast message.
                        <div class="border-top mt-2 pt-2">
                            <b-button type="button" variant="light" size="sm">Take action</b-button> {{ ' ' }}
                            <b-button type="button" variant="secondary" size="sm" data-bs-dismiss="toast">Close</b-button>
                        </div>
                    </b-toast>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="Placement">
                    <form>
                        <div class="mb-3">
                            <label for="selectToastPlacement">Toast placement</label>
                            <b-form-select v-model="selected" :options="options" class="mt-2" id="selectToastPlacement" />
                        </div>
                    </form>
                    <div aria-live="polite" aria-atomic="true" class="bg-light position-relative bd-example-toasts" style="min-height: 294px">
                        <div class="toast-container position-absolute p-3" id="toastPlacement" :class="selected">
                            <b-toast :model-value="true" :noCloseButton="true">
                                <template #title>
                                    <img :src="logoSm" alt="brand-logo" height="16" class="me-1" />
                                    <strong class="me-auto">Admin</strong>
                                    <small>11 mins ago</small>
                                </template>
                                Hello, world! This is a toast message.
                            </b-toast>
                        </div>
                    </div>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { ref } from 'vue';

import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import logoSm from '@/images/logo-sm.png';

const selected = ref(null);
const options = [
    {
        value: null,
        text: 'Select a position...',
    },
    {
        value: 'top-0 start-0',
        text: 'Top left',
    },
    {
        value: 'top-0 start-50 translate-middle-x',
        text: 'Top center',
    },
    {
        value: 'top-0 end-0',
        text: 'Top right',
    },
    {
        value: 'top-50 start-0 translate-middle-y',
        text: 'Middle left',
    },
    {
        value: 'top-50 start-50 translate-middle',
        text: 'Middle center',
    },
    {
        value: 'top-50 end-0 translate-middle-y',
        text: 'Middle right',
    },
    {
        value: 'bottom-0 start-0',
        text: 'Bottom left',
    },
    {
        value: 'bottom-0 start-50 translate-middle-x',
        text: 'Bottom center',
    },
    {
        value: 'bottom-0 end-0',
        text: 'Bottom right',
    },
];
</script>
