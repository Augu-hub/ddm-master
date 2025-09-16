<template>
    <VerticalLayout>
        <PageTitle title="Mixed Charts" subtitle="Apex" />
        <b-row>
            <b-col xl="6">
                <UICard title="Line & Column Chart">
                    <ApexChart id="line-column-mixed" class="apex-charts" :chart="lineColumnChart" />
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Multiple Y-Axis Chart">
                    <ApexChart id="multiple-yaxis-mixed" class="apex-charts" :chart="multipleYAxisChart" />
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Line & Area Chart">
                    <ApexChart id="line-area-mixed" class="apex-charts" :chart="lineAreaChart" />
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Line, Column & Area Chart">
                    <ApexChart id="all-mixed" class="apex-charts" :chart="lineColumnAreaChart" />
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
import { lineAreaChart, lineColumnAreaChart, lineColumnChart, multipleYAxisChart } from '@/pages/charts/mixed/data';
</script>
