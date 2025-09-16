<template>
    <VerticalLayout>
        <PageTitle title="Boxplot Charts" subtitle="Apex" />
        <b-row>
            <b-col xl="6">
                <UICard title="Basic Boxplot">
                    <ApexChart id="basic-boxplot" class="apex-charts" :chart="basicBoxplotChart" />
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Scatter Boxplot">
                    <ApexChart id="scatter-boxplot" class="apex-charts" :chart="scatterBoxplotChart" />
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Horizontal Boxplot">
                    <ApexChart id="basic-heatmap" class="apex-charts" :chart="horizontalBoxplotChart" />
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
import { basicBoxplotChart, horizontalBoxplotChart, scatterBoxplotChart } from '@/pages/charts/boxplot/data';
</script>
