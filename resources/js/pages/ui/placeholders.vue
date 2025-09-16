<template>
    <VerticalLayout>
        <PageTitle title="Placeholders" subtitle="Base UI" />
        <b-row>
            <b-col xl="6">
                <UICard title="Placeholders">
                    <b-row>
                        <b-col md="6">
                            <b-card no-body class="mb-md-0 border shadow-none">
                                <img :src="small1" class="card-img-top" alt="..." />
                                <b-card-body>
                                    <b-card-title tag="h5">Card title</b-card-title>
                                    <p class="card-text">
                                        Some quick example text to build on the card title and make up the bulk of the card's content.
                                    </p>
                                    <a href="#" class="btn btn-primary">Go somewhere</a>
                                </b-card-body>
                            </b-card>
                        </b-col>

                        <b-col md="6">
                            <b-card no-body class="mb-0 border shadow-none" aria-hidden="true">
                                <img :src="small2" class="card-img-top" alt="..." />
                                <b-card-body>
                                    <h5 class="header-title placeholder-glow">
                                        <b-placeholder cols="6" />
                                    </h5>
                                    <p class="card-text placeholder-glow">
                                        <b-placeholder cols="7" />
                                        <b-placeholder cols="4" />
                                        <b-placeholder cols="4" />
                                        <b-placeholder cols="6" />
                                    </p>
                                    <a class="btn btn-primary disabled placeholder col-6" aria-disabled="true">
                                        <span class="invisible">Read Only</span></a
                                    >
                                </b-card-body>
                            </b-card>
                        </b-col>
                    </b-row>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Color">
                    <b-placeholder cols="12" />
                    <b-placeholder cols="12" variant="primary" />
                    <b-placeholder cols="12" variant="secondary" />
                    <b-placeholder cols="12" variant="success" />
                    <b-placeholder cols="12" variant="danger" />
                    <b-placeholder cols="12" variant="warning" />
                    <b-placeholder cols="12" variant="info" />
                    <b-placeholder cols="12" variant="light" />
                    <b-placeholder cols="12" variant="dark" />
                </UICard>

                <UICard title="Width">
                    <b-placeholder cols="6" />
                    <b-placeholder class="w-75" /> <br />
                    <b-placeholder width="25%" /> <br />
                    <b-placeholder width="10%" />
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Sizing">
                    <b-placeholder cols="12" size="lg" />
                    <b-placeholder cols="12" />
                    <b-placeholder cols="12" size="sm" />
                    <b-placeholder cols="12" size="xs" />
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="How it works">
                    <p aria-hidden="true">
                        <b-placeholder cols="6" />
                    </p>

                    <b-placeholder cols="4" tag="a" href="#" class="btn btn-primary disabled" aria-hidden="true" />
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Animation">
                    <b-placeholder animation="glow" cols="12" class="mb-3" />
                    <b-placeholder animation="wave" cols="12" class="mb-0" />
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import small1 from '@/images/small/small-1.jpg';
import small2 from '@/images/small/small-2.jpg';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
</script>
