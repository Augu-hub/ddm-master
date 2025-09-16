<template>
    <VerticalLayout>
        <PageTitle title="Scatter Charts" subtitle="Apex" />
        <b-row>
            <b-col xl="6">
                <UICard title="Scatter (XY) Chart">
                    <div dir="ltr">
                        <ApexChart id="basic-scatter" class="apex-charts" :chart="scatterXYChart" />
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Scatter Chart - Datetime">
                    <div dir="ltr">
                        <ApexChart id="datetime-scatter" class="apex-charts" :chart="dateTimeChart" />
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Scatter - Images">
                    <div dir="ltr">
                        <ApexChart id="scatter-images" class="apex-charts scatter-images-chart" :chart="scatterWithImagesChart" />
                    </div>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import ApexChart from '@/components/ApexChart.vue';
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { dateTimeChart, scatterWithImagesChart, scatterXYChart } from '@/pages/charts/scatter/data';
</script>
