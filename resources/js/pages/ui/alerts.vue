<template>
    <VerticalLayout>
        <PageTitle title="Alerts" subtitle="Base UI" />
        <b-row>
            <b-col xl="6">
                <UICard title="Default Alert">
                    <b-alert variant="primary" class="d-flex align-items-center" :model-value="true">
                        <Icon icon="solar:bell-bing-bold-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Primary - </strong> A simple primary alert — check it out!</div>
                    </b-alert>
                    <b-alert variant="secondary" class="d-flex align-items-center" :model-value="true">
                        <Icon icon="solar:bicycling-round-bold-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Secondary - </strong> A simple secondary alert — check it out!</div>
                    </b-alert>
                    <b-alert variant="success" class="d-flex align-items-center" :model-value="true">
                        <Icon icon="solar:check-read-line-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Success - </strong> A simple success alert — check it out!</div>
                    </b-alert>
                    <b-alert variant="danger" class="d-flex align-items-center" :model-value="true">
                        <Icon icon="solar:danger-triangle-bold-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Error - </strong> A simple danger alert — check it out!</div>
                    </b-alert>
                    <b-alert variant="warning" class="d-flex align-items-center" :model-value="true">
                        <Icon icon="solar:shield-warning-line-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Warning - </strong> A simple warning alert — check it out!</div>
                    </b-alert>
                    <b-alert variant="info" class="d-flex align-items-center" :model-value="true">
                        <Icon icon="solar:info-circle-bold-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Info - </strong> A simple info alert — check it out!</div>
                    </b-alert>
                    <b-alert variant="light" class="d-flex align-items-center" :model-value="true">
                        <Icon icon="solar:atom-bold-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Light - </strong> A simple light alert — check it out!</div>
                    </b-alert>
                    <b-alert variant="dark" class="d-flex align-items-center mb-0" :model-value="true">
                        <Icon icon="solar:balloon-bold-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Dark - </strong> A simple dark alert — check it out!</div>
                    </b-alert>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Dismissing Alert">
                    <b-alert
                        variant="primary"
                        class="text-bg-primary d-flex align-items-center"
                        close-class="btn-close-white"
                        :model-value="true"
                        dismissible
                    >
                        <Icon icon="solar:bell-bing-bold-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Primary - </strong> A simple primary alert — check it out!</div>
                    </b-alert>
                    <b-alert
                        variant="secondary"
                        class="text-bg-secondary d-flex align-items-center"
                        close-class="btn-close-white"
                        :model-value="true"
                        dismissible
                    >
                        <Icon icon="solar:bicycling-round-bold-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Secondary - </strong> A simple secondary alert — check it out!</div>
                    </b-alert>
                    <b-alert
                        variant="success"
                        class="text-bg-success d-flex align-items-center"
                        close-class="btn-close-white"
                        :model-value="true"
                        dismissible
                    >
                        <Icon icon="solar:check-read-line-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Success - </strong> A simple success alert — check it out!</div>
                    </b-alert>
                    <b-alert
                        variant="danger"
                        class="text-bg-danger d-flex align-items-center"
                        close-class="btn-close-white"
                        :model-value="true"
                        dismissible
                    >
                        <Icon icon="solar:danger-triangle-bold-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Error - </strong> A simple danger alert — check it out!</div>
                    </b-alert>
                    <b-alert
                        variant="warning"
                        class="text-bg-warning d-flex align-items-center"
                        close-class="btn-close-white"
                        :model-value="true"
                        dismissible
                    >
                        <Icon icon="solar:shield-warning-line-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Warning - </strong> A simple warning alert — check it out!</div>
                    </b-alert>
                    <b-alert
                        variant="info"
                        class="text-bg-info d-flex align-items-center"
                        close-class="btn-close-white"
                        :model-value="true"
                        dismissible
                    >
                        <Icon icon="solar:info-circle-bold-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Info - </strong> A simple info alert — check it out!</div>
                    </b-alert>
                    <b-alert variant="light" class="text-bg-light d-flex align-items-center" :model-value="true" dismissible>
                        <Icon icon="solar:atom-bold-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Light - </strong> A simple light alert — check it out!</div>
                    </b-alert>
                    <b-alert
                        variant="dark"
                        class="text-bg-dark d-flex align-items-center mb-0"
                        close-class="btn-close-white"
                        :model-value="true"
                        dismissible
                    >
                        <Icon icon="solar:balloon-bold-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Dark - </strong> A simple dark alert — check it out!</div>
                    </b-alert>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Link Color">
                    <b-alert variant="primary" :model-value="true">
                        A simple primary alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.
                    </b-alert>
                    <b-alert variant="secondary" :model-value="true">
                        A simple secondary alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.
                    </b-alert>
                    <b-alert variant="success" :model-value="true">
                        A simple success alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.
                    </b-alert>
                    <b-alert variant="danger" :model-value="true">
                        A simple danger alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.
                    </b-alert>
                    <b-alert variant="warning" :model-value="true">
                        A simple warning alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.
                    </b-alert>
                    <b-alert variant="info" :model-value="true">
                        A simple info alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.
                    </b-alert>
                    <b-alert variant="light" :model-value="true">
                        A simple light alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.
                    </b-alert>
                    <b-alert variant="dark" :model-value="true">
                        A simple dark alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.
                    </b-alert>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Custom Alerts">
                    <b-alert variant="primary" class="d-flex align-items-center border-primary border border-2" :model-value="true" dismissible>
                        <Icon icon="solar:bell-bing-bold-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Primary - </strong> A simple primary alert — check it out!</div>
                    </b-alert>
                    <b-alert variant="secondary" class="d-flex align-items-center border-secondary border border-2" :model-value="true" dismissible>
                        <Icon icon="solar:bicycling-round-bold-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Secondary - </strong> A simple secondary alert — check it out!</div>
                    </b-alert>
                    <b-alert variant="success" class="d-flex align-items-center border-success border border-2" :model-value="true" dismissible>
                        <Icon icon="solar:check-read-line-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Success - </strong> A simple success alert — check it out!</div>
                    </b-alert>
                    <b-alert variant="danger" class="d-flex align-items-center border-danger border border-2" :model-value="true" dismissible>
                        <Icon icon="solar:danger-triangle-bold-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Error - </strong> A simple danger alert — check it out!</div>
                    </b-alert>
                    <b-alert variant="warning" class="d-flex align-items-center border-warning border" :model-value="true" dismissible>
                        <Icon icon="solar:shield-warning-line-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Warning - </strong> A simple warning alert — check it out!</div>
                    </b-alert>
                    <b-alert variant="info" class="d-flex align-items-center border-info border" :model-value="true" dismissible>
                        <Icon icon="solar:info-circle-bold-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Info - </strong> A simple info alert — check it out!</div>
                    </b-alert>
                    <b-alert variant="light" class="d-flex align-items-center border-light border" :model-value="true" dismissible>
                        <Icon icon="solar:atom-bold-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Light - </strong> A simple light alert — check it out!</div>
                    </b-alert>
                    <b-alert variant="dark" class="d-flex align-items-center border-dark mb-0 border" :model-value="true" dismissible>
                        <Icon icon="solar:balloon-bold-duotone" class="fs-20 me-1" />
                        <div class="lh-1"><strong>Dark - </strong> A simple dark alert — check it out!</div>
                    </b-alert>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Additional Content">
                    <b-alert variant="success" class="p-3" :model-value="true">
                        <h4 class="alert-heading">Well done!</h4>
                        <p>
                            Aww yeah, you successfully read this important alert message. This example text is going to run a bit longer so that you
                            can see how spacing within an alert works with this kind of content.
                        </p>
                        <hr class="border-success border-opacity-25" />
                        <p class="mb-0">Whenever you need to, be sure to use margin utilities to keep things nice and tidy.</p>
                    </b-alert>
                    <b-alert variant="secondary" class="d-flex p-3" :model-value="true">
                        <Icon icon="solar:bell-bing-bold-duotone" class="fs-1 me-2" />
                        <div>
                            <h4 class="alert-heading">Well done!</h4>
                            <p>
                                Aww yeah, you successfully read this important alert message. This example text is going to run a bit longer so that
                                you can see how spacing within an alert works with this kind of content.
                            </p>
                            <hr class="border-secondary border-opacity-25" />
                            <p class="mb-0">Whenever you need to, be sure to use margin utilities to keep things nice and tidy.</p>
                        </div>
                    </b-alert>
                    <b-alert variant="primary" class="d-flex mb-0 p-3" :model-value="true">
                        <Icon icon="solar:atom-bold-duotone" class="fs-1 me-2" />
                        <div>
                            <h4 class="alert-heading">Thank you!</h4>
                            <p>
                                Aww yeah, you successfully read this important alert message. This example text is going to run a bit longer so that
                                you can see how spacing within an alert works with this kind of content.
                            </p>
                            <b-button type="button" size="sm" variant="primary">Close</b-button>
                        </div>
                    </b-alert>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Live Alert">
                    <b-alert variant="success" v-model="liveAlert" dismissible>
                        <div>Nice, you triggered this alert message!</div>
                    </b-alert>

                    <div id="liveAlertPlaceholder"></div>
                    <b-button type="button" variant="primary" id="liveAlertBtn" @click="liveAlert = true">Show live alert</b-button>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { Icon } from '@iconify/vue';
import { ref } from 'vue';

const liveAlert = ref(false);
</script>
