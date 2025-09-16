<template>
    <VerticalLayout>
        <PageTitle title="Badges" subtitle="Base UI" />
        <b-row>
            <b-col xl="6">
                <UICard title="Default">
                    <h1>h1.Example heading <b-badge :variant="null" text-variant="secondary" class="bg-secondary-subtle">New</b-badge></h1>
                    <h2>h2.Example heading <b-badge :variant="null" class="badge-soft-success">New</b-badge></h2>
                    <h3>h2.Example heading <b-badge variant="primary">New</b-badge></h3>
                    <h4>h4.Example heading <b-badge tag="a" :variant="null" href="#" class="badge-soft-info">Info Link</b-badge></h4>
                    <h5>h5.Example heading <b-badge :variant="null" class="badge-outline-warning">New</b-badge></h5>
                    <h6>h6.Example heading <b-badge variant="danger">New</b-badge></h6>
                </UICard>

                <UICard title="Pill Badges">
                    <div class="hstack gap-1">
                        <b-badge variant="primary" pill>Primary</b-badge>
                        <b-badge variant="secondary" pill class="text-bg-secondary">Secondary</b-badge>
                        <b-badge variant="success" pill>Success</b-badge>
                        <b-badge variant="danger" pill>Danger</b-badge>
                        <b-badge variant="warning" pill>Warning</b-badge>
                        <b-badge variant="info" pill>Info</b-badge>
                        <b-badge variant="light" text-variant="dark" pill>Light</b-badge>
                        <b-badge variant="dark" text-variant="light" pill>Dark</b-badge>
                    </div>

                    <h5 class="mt-4">Lighten Badges</h5>
                    <div class="hstack gap-1">
                        <b-badge :variant="null" pill class="badge-soft-primary">Primary</b-badge>
                        <b-badge :variant="null" pill class="badge-soft-secondary">Secondary</b-badge>
                        <b-badge :variant="null" pill class="badge-soft-success">Success</b-badge>
                        <b-badge :variant="null" pill class="badge-soft-danger">Danger</b-badge>
                        <b-badge :variant="null" pill class="badge-soft-warning">Warning</b-badge>
                        <b-badge :variant="null" pill class="badge-soft-info">Info</b-badge>
                        <b-badge :variant="null" pill class="badge-soft-dark">Dark</b-badge>
                    </div>

                    <h5 class="mt-4">Outline Badges</h5>
                    <div class="hstack gap-1">
                        <b-badge :variant="null" pill class="badge-outline-primary">Primary</b-badge>
                        <b-badge :variant="null" pill class="badge-outline-secondary">Secondary</b-badge>
                        <b-badge :variant="null" pill class="badge-outline-success">Success</b-badge>
                        <b-badge :variant="null" pill class="badge-outline-danger">Danger</b-badge>
                        <b-badge :variant="null" pill class="badge-outline-warning">Warning</b-badge>
                        <b-badge :variant="null" pill class="badge-outline-info">Info</b-badge>
                        <b-badge :variant="null" pill class="badge-outline-dark">Dark</b-badge>
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Contextual variations">
                    <div class="hstack gap-1">
                        <b-badge variant="primary">Primary</b-badge>
                        <b-badge variant="secondary" class="text-bg-secondary">Secondary</b-badge>
                        <b-badge variant="success">Success</b-badge>
                        <b-badge variant="danger">Danger</b-badge>
                        <b-badge variant="warning">Warning</b-badge>
                        <b-badge variant="info">Info</b-badge>
                        <b-badge variant="light" text-variant="dark">Light</b-badge>
                        <b-badge variant="dark" text-variant="light">Dark</b-badge>
                    </div>
                    <h5 class="mt-4">Lighten Badges</h5>
                    <div class="hstack gap-1">
                        <b-badge :variant="null" class="badge-soft-primary">Primary</b-badge>
                        <b-badge :variant="null" class="badge-soft-secondary">Secondary</b-badge>
                        <b-badge :variant="null" class="badge-soft-success">Success</b-badge>
                        <b-badge :variant="null" class="badge-soft-danger">Danger</b-badge>
                        <b-badge :variant="null" class="badge-soft-warning">Warning</b-badge>
                        <b-badge :variant="null" class="badge-soft-info">Info</b-badge>
                        <b-badge :variant="null" class="badge-soft-dark">Dark</b-badge>
                    </div>
                    <h5 class="mt-4">Outline Badges</h5>
                    <div class="hstack gap-1">
                        <b-badge :variant="null" class="badge-outline-primary">Primary</b-badge>
                        <b-badge :variant="null" class="badge-outline-secondary">Secondary</b-badge>
                        <b-badge :variant="null" class="badge-outline-success">Success</b-badge>
                        <b-badge :variant="null" class="badge-outline-danger">Danger</b-badge>
                        <b-badge :variant="null" class="badge-outline-warning">Warning</b-badge>
                        <b-badge :variant="null" class="badge-outline-info">Info</b-badge>
                        <b-badge :variant="null" class="badge-outline-dark">Dark</b-badge>
                    </div>
                </UICard>

                <UICard title="Badge Positioned">
                    <b-row>
                        <b-col cols="6">
                            <b-button type="button" variant="primary" class="position-relative">
                                Inbox
                                <span class="position-absolute translate-middle badge rounded-pill bg-danger start-100 top-0">
                                    99+
                                    <span class="visually-hidden">unread messages</span>
                                </span>
                            </b-button>
                        </b-col>
                        <b-col cols="6">
                            <b-button type="button" variant="primary" class="position-relative">
                                Profile
                                <span class="position-absolute translate-middle bg-danger border-light rounded-circle start-100 top-0 border p-1">
                                    <span class="visually-hidden">New alerts</span>
                                </span>
                            </b-button>
                        </b-col>
                        <b-col cols="6">
                            <b-button type="button" variant="success" class="mt-4">
                                Notifications <b-badge variant="light" text-variant="dark" class="ms-1">4</b-badge>
                            </b-button>
                        </b-col>
                    </b-row>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
</script>
