<template>
    <VerticalLayout>
        <PageTitle title="Buttons" subtitle="Base UI" />
        <b-row>
            <b-col xl="6">
                <UICard title="Default Buttons">
                    <div class="d-flex flex-wrap gap-2">
                        <b-button type="button" variant="primary">Primary</b-button>
                        <b-button type="button" variant="secondary">Secondary</b-button>
                        <b-button type="button" variant="success">Success</b-button>
                        <b-button type="button" variant="danger">Danger</b-button>
                        <b-button type="button" variant="warning">Warning</b-button>
                        <b-button type="button" variant="info">Info</b-button>
                        <b-button type="button" variant="light">Light</b-button>
                        <b-button type="button" variant="dark">Dark</b-button>
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Button Outline">
                    <div class="d-flex flex-wrap gap-2">
                        <b-button type="button" variant="outline-primary">Primary</b-button>
                        <b-button type="button" variant="outline-secondary">Secondary</b-button>
                        <b-button type="button" variant="outline-success"> Success</b-button>
                        <b-button type="button" variant="outline-danger">Danger</b-button>
                        <b-button type="button" variant="outline-warning">Warning</b-button>
                        <b-button type="button" variant="outline-info">Info</b-button>
                        <b-button type="button" variant="outline-light">Light</b-button>
                        <b-button type="button" variant="outline-dark">Dark</b-button>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Button-Rounded">
                    <div class="d-flex flex-wrap gap-2">
                        <b-button type="button" variant="primary" pill>Primary</b-button>
                        <b-button type="button" variant="secondary" pill>Secondary</b-button>
                        <b-button type="button" variant="success" pill>Success</b-button>
                        <b-button type="button" variant="danger" pill>Danger</b-button>
                        <b-button type="button" variant="warning" pill>Warning</b-button>
                        <b-button type="button" variant="info" pill>Info</b-button>
                        <b-button type="button" variant="light" pill>Light</b-button>
                        <b-button type="button" variant="dark" pill>Dark</b-button>
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Button Outline Rounded">
                    <div class="d-flex flex-wrap gap-2">
                        <b-button type="button" variant="outline-primary" pill>Primary</b-button>
                        <b-button type="button" variant="outline-secondary" pill>Secondary</b-button>
                        <b-button type="button" variant="outline-success" pill>Success</b-button>
                        <b-button type="button" variant="outline-danger" pill>Danger</b-button>
                        <b-button type="button" variant="outline-warning" pill>Warning</b-button>
                        <b-button type="button" variant="outline-info" pill>Info</b-button>
                        <b-button type="button" variant="outline-dark" pill>Dark</b-button>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Soft Buttons">
                    <div class="d-flex flex-wrap gap-2">
                        <b-button type="button" class="btn-soft-primary">Primary</b-button>
                        <b-button type="button" class="btn-soft-secondary">Secondary</b-button>
                        <b-button type="button" class="btn-soft-success">Success</b-button>
                        <b-button type="button" class="btn-soft-danger">Danger</b-button>
                        <b-button type="button" class="btn-soft-warning">Warning</b-button>
                        <b-button type="button" class="btn-soft-info">Info</b-button>
                        <b-button type="button" class="btn-soft-dark">Dark</b-button>
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Soft Rounded Buttons">
                    <div class="d-flex flex-wrap gap-2">
                        <b-button type="button" pill class="btn-soft-primary">Primary</b-button>
                        <b-button type="button" pill class="btn-soft-secondary">Secondary</b-button>
                        <b-button type="button" pill class="btn-soft-success">Success</b-button>
                        <b-button type="button" pill class="btn-soft-danger">Danger</b-button>
                        <b-button type="button" pill class="btn-soft-warning">Warning</b-button>
                        <b-button type="button" pill class="btn-soft-info">Info</b-button>
                        <b-button type="button" pill class="btn-soft-dark">Dark</b-button>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Gradient Buttons">
                    <div class="d-flex flex-wrap gap-2">
                        <b-button type="button" variant="primary" class="btn-primary bg-gradient">Primary</b-button>
                        <b-button type="button" variant="secondary" class="btn-secondary bg-gradient">Secondary</b-button>
                        <b-button type="button" variant="success" class="btn-success bg-gradient">Success</b-button>
                        <b-button type="button" variant="danger" class="btn-danger bg-gradient">Danger</b-button>
                        <b-button type="button" :variant="null" class="btn-soft-warning bg-gradient">Warning</b-button>
                        <b-button type="button" :variant="null" class="btn-soft-info bg-gradient">Info</b-button>
                        <b-button type="button" :variant="null" class="btn-soft-dark bg-gradient">Dark</b-button>
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Gradient Rounded Buttons">
                    <div class="d-flex flex-wrap gap-2">
                        <b-button type="button" pill variant="primary" class="bg-gradient">Primary</b-button>
                        <b-button type="button" pill variant="secondary" class="bg-gradient">Secondary</b-button>
                        <b-button type="button" pill :variant="null" class="btn btn-soft-success bg-gradient">Success</b-button>
                        <b-button type="button" pill :variant="null" class="btn btn-soft-danger bg-gradient">Danger</b-button>
                        <b-button type="button" pill :variant="null" class="btn btn-soft-warning bg-gradient">Warning</b-button>
                        <b-button type="button" pill variant="info" class="bg-gradient">Info</b-button>
                        <b-button type="button" pill variant="dark" class="bg-gradient">Dark</b-button>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Ghost Buttons">
                    <div class="d-flex flex-wrap gap-2">
                        <b-button type="button" :variant="null" class="btn-ghost-primary">Primary</b-button>
                        <b-button type="button" :variant="null" class="btn-ghost-secondary">Secondary</b-button>
                        <b-button type="button" :variant="null" class="btn-ghost-success">Success</b-button>
                        <b-button type="button" :variant="null" class="btn-ghost-danger">Danger</b-button>
                        <b-button type="button" :variant="null" class="btn-ghost-warning">Warning</b-button>
                        <b-button type="button" :variant="null" class="btn-ghost-info">Info</b-button>
                        <b-button type="button" :variant="null" class="btn-ghost-dark">Dark</b-button>
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Ghost Rounded Buttons">
                    <div class="d-flex flex-wrap gap-2">
                        <b-button type="button" :variant="null" pill class="btn-ghost-primary">Primary</b-button>
                        <b-button type="button" :variant="null" pill class="btn-ghost-secondary">Secondary</b-button>
                        <b-button type="button" :variant="null" pill class="btn-ghost-success">Success</b-button>
                        <b-button type="button" :variant="null" pill class="btn-ghost-danger">Danger</b-button>
                        <b-button type="button" :variant="null" pill class="btn-ghost-warning">Warning</b-button>
                        <b-button type="button" :variant="null" pill class="btn-ghost-info">Info</b-button>
                        <b-button type="button" :variant="null" pill class="btn-ghost-dark">Dark</b-button>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Button-Sizes">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <b-button type="button" variant="primary" size="lg">Large</b-button>
                        <b-button type="button" variant="info">Normal</b-button>
                        <b-button type="button" variant="success" size="sm">Small</b-button>
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Button-Disabled">
                    <div class="d-flex flex-wrap gap-2">
                        <b-button type="button" variant="info" disabled>Info</b-button>
                        <b-button type="button" variant="success" disabled>Success</b-button>
                        <b-button type="button" variant="danger" disabled>Danger</b-button>
                        <b-button type="button" variant="dark" disabled>Dark</b-button>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Icon Buttons">
                    <div class="d-flex flex-wrap gap-2">
                        <b-button type="button" variant="light" class="btn-icon"><i class="ti ti-heart fs-16"></i> </b-button>
                        <b-button type="button" variant="danger" class="btn-icon"><AppleIcon class="avatar-xxs" /></b-button>
                        <b-button type="button" variant="dark" class="btn-icon"><i class="ti ti-adjustments-alt fs-18"></i> </b-button>
                        <b-button type="button" :variant="null" class="btn-soft-primary btn-icon"
                            ><Icon icon="solar:add-circle-bold-duotone" class="fs-20" />
                        </b-button>
                        <b-button type="button" :variant="null" class="btn-soft-success btn-icon"
                            ><i class="ti ti-alert-hexagon fs-20"></i>
                        </b-button>
                        <b-button type="button" variant="info" class="btn-icon"><i class="ti ti-ambulance fs-18"></i> </b-button>
                        <b-button type="button" :variant="null" class="btn-soft-warning btn-icon"><i class="ti ti-music fs-18"></i> </b-button>
                        <b-button type="button" variant="light"><i class="ti ti-thumb-up fs-18 me-1 align-middle"></i> Like</b-button>
                        <b-button type="button" variant="warning"><ActivityIcon class="avatar-xxs me-1" /> Launch</b-button>
                        <b-button type="button" variant="outline-success"><i class="ti ti-pig-money fs-18 me-1 align-middle"></i> Money</b-button>
                        <b-button type="button" variant="outline-primary"><i class="ti ti-brand-paypal fs-18 me-1 align-middle"></i> PayPal</b-button>
                        <b-button type="button" :variant="null" class="btn-soft-danger"
                            ><Icon icon="solar:settings-bold-duotone" class="fs-18 me-1 align-middle" /> <span>Settings</span></b-button
                        >
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Block Button">
                    <div class="d-grid gap-2">
                        <b-button type="button" variant="primary" size="sm">Block Button</b-button>
                        <b-button type="button" variant="success" size="lg">Block Button</b-button>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Button Group">
                    <b-button-group class="mb-2">
                        <b-button variant="light">Left</b-button>
                        <b-button variant="light">Middle</b-button>
                        <b-button variant="light">Right</b-button>
                    </b-button-group>
                    <br />
                    <b-button-group class="mb-2">
                        <b-button variant="light">1</b-button>
                        <b-button variant="light">2</b-button>
                        <b-button variant="light">3</b-button>
                        <b-button variant="light">4</b-button>
                    </b-button-group>
                    <b-button-group class="mb-2">
                        <b-button variant="light">5</b-button>
                        <b-button variant="light">6</b-button>
                        <b-button variant="light">7</b-button>
                    </b-button-group>
                    <b-button-group class="mb-2">
                        <b-button variant="light">8</b-button>
                    </b-button-group>
                    <br />
                    <b-button-group class="mb-2">
                        <b-button variant="light">1</b-button>
                        <b-button variant="primary">2</b-button>
                        <b-button variant="light">3</b-button>
                        <button id="dropdown" type="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            Dropdown
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown" style="">
                            <li><a class="dropdown-item" href="javascript:void(0);">Dropdown link</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Dropdown link</a></li>
                        </ul>
                    </b-button-group>

                    <b-row>
                        <div class="col-md-3">
                            <b-button-group vertical class="mb-2">
                                <b-button variant="light">Top</b-button>
                                <b-button variant="light">Middle</b-button>
                                <b-button variant="light">Bottom</b-button>
                            </b-button-group>
                        </div>
                        <div class="col-md-3">
                            <b-button-group vertical class="mb-2">
                                <b-button variant="light">Button 1</b-button>
                                <b-button variant="light">Button 2</b-button>
                                <button
                                    id="dropdown"
                                    type="button"
                                    class="btn btn-light dropdown-toggle"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    Button 3
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdown" style="">
                                    <li><a class="dropdown-item" href="javascript:void(0);">Dropdown link</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">Dropdown link</a></li>
                                </ul>
                            </b-button-group>
                        </div>
                    </b-row>
                </UICard>
            </b-col>

            <b-col xl="6">
                <b-row>
                    <b-col xl="12">
                        <UICard title="Toggle Button">
                            <div class="d-flex flex-wrap gap-2">
                                <b-button type="button" variant="primary" data-bs-toggle="button">Toggle button</b-button>
                                <b-button type="button" variant="primary" class="active" data-bs-toggle="button" aria-pressed="true"
                                    >Active toggle button</b-button
                                >
                                <b-button type="button" variant="primary" disabled data-bs-toggle="button">Disabled toggle button</b-button>
                            </div>
                        </UICard>
                    </b-col>
                    <b-col xl="12">
                        <UICard title="Button tags">
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-primary" href="#" role="button">Link</a>
                                <b-button variant="primary" type="submit">Button</b-button>
                                <input class="btn btn-primary" type="button" value="Input" />
                                <input class="btn btn-primary" type="submit" value="Submit" />
                                <input class="btn btn-primary" type="reset" value="Reset" />
                            </div>
                        </UICard>
                    </b-col>
                </b-row>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Basic Button">
                    <b-button type="button" :variant="null">Base class</b-button>
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
import { ActivityIcon, AppleIcon } from 'lucide-vue-next';
</script>
