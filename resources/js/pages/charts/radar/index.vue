<template>
    <VerticalLayout>
        <PageTitle title="Radar Charts" subtitle="Apex" />
        <b-row>
            <b-col xl="6">
                <UICard title="Basic Radar Chart">
                    <ApexChart :chart="basicRadarChart" class="apex-charts" id="basic-radar" />
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Radar with Polygon-fill">
                    <ApexChart :chart="polygonFillChart" class="apex-charts" id="radar-polygon" />
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Radar – Multiple Series">
                    <div dir="ltr">
                        <apexchart type="radar" height="350" :options="multipleSeriesChart" :series="series" class="apex-charts" />
                        <div class="mt-2 text-center">
                            <b-button @click="update" variant="primary" size="sm"> Update</b-button>
                        </div>
                    </div>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { basicRadarChart, multipleSeriesChart, polygonFillChart } from '@/pages/charts/radar/data';

import ApexChart from '@/components/ApexChart.vue';
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import { ref } from 'vue';

const series = ref([
    {
        name: 'Series 1',
        data: [80, 50, 30, 40, 100, 20],
    },
    {
        name: 'Series 2',
        data: [20, 30, 40, 80, 20, 80],
    },
    {
        name: 'Series 3',
        data: [44, 76, 78, 13, 43, 10],
    },
]);

const update = () => {
    function randomSeries() {
        const arr = [];
        for (let i = 0; i < 6; i++) {
            arr.push(Math.floor(Math.random() * 100));
        }
        return arr;
    }

    series.value = [
        {
            name: 'Series 1',
            data: randomSeries(),
        },
        {
            name: 'Series 2',
            data: randomSeries(),
        },
        {
            name: 'Series 3',
            data: randomSeries(),
        },
    ];
};
</script>
