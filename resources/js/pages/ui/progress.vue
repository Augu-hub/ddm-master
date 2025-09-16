<template>
    <VerticalLayout>
        <PageTitle title="Progress" subtitle="Base UI" />
        <b-row>
            <b-col xl="6">
                <UICard title="Examples">
                    <b-progress class="mb-2" :value="0" />
                    <b-progress class="mb-2" :value="25" />
                    <b-progress class="mb-2" :value="50" />
                    <b-progress class="mb-2" :value="75" />
                    <b-progress :value="100" />
                </UICard>

                <UICard title="Height">
                    <b-progress class="mb-2" variant="danger" style="height: 1px" :value="25" />
                    <b-progress class="mb-2" variant="primary" style="height: 3px" :value="25" />
                    <b-progress class="progress-sm mb-2" variant="success" :value="25" />
                    <b-progress class="progress-md mb-2" variant="info" :value="50" />
                    <b-progress class="progress-lg mb-2" variant="warning" :value="75" />
                    <b-progress class="progress-xl mb-2" variant="success" :value="38" />
                </UICard>

                <UICard title="Multiple Bars">
                    <b-progress>
                        <b-progress-bar variant="primary" :value="15" />
                        <b-progress-bar variant="success" :value="30" />
                        <b-progress-bar variant="info" :value="20" />
                    </b-progress>
                </UICard>

                <UICard title="Animated Stripes">
                    <b-progress class="mb-2" variant="primary" striped animated :value="75" />
                </UICard>

                <UICard title="Steps">
                    <div class="position-relative m-4">
                        <b-progress style="height: 2px">
                            <b-progress-bar variant="primary" :value="50" />
                        </b-progress>
                        <b-button type="button" variant="primary" pill class="position-absolute translate-middle btn-icon start-0 top-0">1</b-button>
                        <b-button type="button" variant="primary" pill class="position-absolute translate-middle btn-icon start-50 top-0">2</b-button>
                        <b-button type="button" variant="light" pill class="position-absolute translate-middle btn-icon start-100 top-0">3</b-button>
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Labels">
                    <b-progress class="mb-3">
                        <b-progress-bar variant="primary" :value="25"> 25% </b-progress-bar>
                    </b-progress>

                    <b-progress>
                        <b-progress-bar class="text-dark overflow-visible" variant="primary" :value="10">
                            Long label text for the progress bar, set to a dark color
                        </b-progress-bar>
                    </b-progress>
                </UICard>

                <UICard title="Backgrounds">
                    <b-progress class="mb-2" variant="success" :value="25" />
                    <b-progress class="mb-2" variant="info" :value="50" />
                    <b-progress class="mb-2" variant="warning" :value="75" />
                    <b-progress class="mb-2" variant="danger" :value="100" />
                    <b-progress class="mb-2" variant="dark" :value="65" />
                    <b-progress variant="secondary" :value="50" />
                </UICard>

                <UICard title="Backgrounds (Custom)">
                    <b-progress class="progress-soft mb-2">
                        <b-progress-bar bg-variant="success" :value="25" />
                    </b-progress>
                    <b-progress class="progress-soft mb-2">
                        <b-progress-bar bg-variant="info" :value="50" />
                    </b-progress>
                    <b-progress class="progress-soft mb-2">
                        <b-progress-bar bg-variant="warning" :value="75" />
                    </b-progress>
                    <b-progress class="progress-soft mb-2">
                        <b-progress-bar bg-variant="danger" :value="100" />
                    </b-progress>
                    <b-progress class="progress-soft mb-2">
                        <b-progress-bar bg-variant="dark" :value="65" />
                    </b-progress>
                    <b-progress class="progress-soft">
                        <b-progress-bar bg-variant="secondary" :value="50" />
                    </b-progress>
                </UICard>

                <UICard title="Striped">
                    <b-progress bg-variant="primary" striped class="mb-2" :value="10" />
                    <b-progress bg-variant="success" striped class="mb-2" :value="25" />
                    <b-progress bg-variant="info" striped class="mb-2" :value="50" />
                    <b-progress bg-variant="warning" striped class="mb-2" :value="75" />
                    <b-progress bg-variant="danger" striped :value="100" />
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
