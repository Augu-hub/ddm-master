<template>
    <VerticalLayout>
        <PageTitle title="Spinners" subtitle="Base UI" />
        <b-row>
            <b-col xl="6">
                <UICard title="Border Spinner">
                    <b-spinner class="m-2" />
                </UICard>

                <UICard title="Colors">
                    <b-spinner variant="primary" class="m-2" />
                    <b-spinner variant="secondary" class="m-2" />
                    <b-spinner variant="success" class="m-2" />
                    <b-spinner variant="danger" class="m-2" />
                    <b-spinner variant="warning" class="m-2" />
                    <b-spinner variant="info" class="m-2" />
                    <b-spinner variant="light" class="m-2" />
                    <b-spinner variant="dark" class="m-2" />
                </UICard>

                <UICard title="Alignment">
                    <div class="d-flex justify-content-center">
                        <b-spinner />
                    </div>
                </UICard>

                <UICard title="Size">
                    <b-row>
                        <b-col lg="6">
                            <b-spinner variant="primary" class="avatar-lg m-2" />
                            <b-spinner variant="secondary" class="avatar-lg m-2" type="grow" />
                        </b-col>

                        <b-col lg="6">
                            <b-spinner variant="primary" class="avatar-md m-2" />
                            <b-spinner variant="secondary" class="avatar-md m-2" type="grow" />
                        </b-col>

                        <b-col lg="6">
                            <b-spinner variant="primary" class="avatar-sm m-2" />
                            <b-spinner variant="secondary" class="avatar-sm m-2" type="grow" />
                        </b-col>

                        <b-col lg="6">
                            <b-spinner small class="m-2" />
                            <b-spinner small class="m-2" type="grow" />
                        </b-col>
                    </b-row>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Growing Spinner">
                    <b-spinner class="m-2" type="grow" />
                </UICard>

                <UICard title="Color Growing Spinner">
                    <b-spinner variant="primary" class="m-2" type="grow" />
                    <b-spinner variant="secondary" class="m-2" type="grow" />
                    <b-spinner variant="success" class="m-2" type="grow" />
                    <b-spinner variant="danger" class="m-2" type="grow" />
                    <b-spinner variant="warning" class="m-2" type="grow" />
                    <b-spinner variant="info" class="m-2" type="grow" />
                    <b-spinner variant="light" class="m-2" type="grow" />
                    <b-spinner variant="dark" class="m-2" type="grow" />
                </UICard>

                <UICard title="Placement">
                    <div class="d-flex align-items-center">
                        <strong>Loading...</strong>
                        <b-spinner class="ms-auto" />
                    </div>
                </UICard>

                <UICard title="Buttons Spinner">
                    <b-row>
                        <b-col lg="6">
                            <div class="d-flex flex-wrap gap-2">
                                <b-button variant="primary" type="button" disabled>
                                    <b-spinner small />
                                </b-button>
                                <b-button variant="primary" type="button" disabled>
                                    <b-spinner small class="me-1" />
                                    Loading...
                                </b-button>
                            </div>
                        </b-col>
                        <b-col lg="6">
                            <div class="d-flex flex-wrap gap-2">
                                <b-button variant="primary" type="button" disabled>
                                    <b-spinner small type="grow" />
                                </b-button>
                                <b-button variant="primary" type="button" disabled>
                                    <b-spinner small class="me-1" type="grow" />
                                    Loading...
                                </b-button>
                            </div>
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
