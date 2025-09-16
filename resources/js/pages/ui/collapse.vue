<template>
    <VerticalLayout>
        <PageTitle title="Collapse" subtitle="Base UI" />
        <b-row>
            <b-col xl="6">
                <UICard title="Collapse">
                    <p>
                        <a class="btn btn-primary" href="#" v-b-toggle.collapseExample> Link with href </a>
                        <b-button variant="primary" class="ms-1" type="button" v-b-toggle.collapseExample> Button with data-bs-target </b-button>
                    </p>
                    <b-collapse id="collapseExample" visible>
                        <b-card no-body class="card-body mb-0">
                            Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. Nihil anim keffiyeh
                            helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident.
                        </b-card>
                    </b-collapse>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Collapse Horizontal">
                    <p>
                        <b-button variant="primary" type="button" v-b-toggle.collapseWidthExample> Toggle width collapse </b-button>
                    </p>
                    <div style="min-height: 105px">
                        <b-collapse horizontal class="collapse-horizontal" id="collapseWidthExample">
                            <b-card no-body class="card-body mb-0" style="width: 300px">
                                This is some placeholder content for a horizontal collapse. It's hidden by default and shown when triggered.
                            </b-card>
                        </b-collapse>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Multiple Targets">
                    <p class="hstack gap-1">
                        <a class="btn btn-primary" v-b-toggle.multiCollapseExample1> Toggle first element </a>
                        <b-button variant="primary" type="button" v-b-toggle.multiCollapseExample2> Toggle second element </b-button>
                        <b-button variant="primary" type="button" v-b-toggle="['multiCollapseExample1', 'multiCollapseExample2']">
                            Toggle both elements
                        </b-button>
                    </p>
                    <b-row>
                        <b-col>
                            <b-collapse class="multi-collapse" id="multiCollapseExample1">
                                <b-card no-body class="card-body mb-0">
                                    Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. Nihil anim
                                    keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident.
                                </b-card>
                            </b-collapse>
                        </b-col>
                        <b-col>
                            <b-collapse class="multi-collapse" id="multiCollapseExample2">
                                <b-card no-body class="card-body mb-0">
                                    Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. Nihil anim
                                    keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident.
                                </b-card>
                            </b-collapse>
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
