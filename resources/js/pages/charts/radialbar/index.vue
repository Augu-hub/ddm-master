<template>
    <VerticalLayout>
        <PageTitle title="Radial Charts" subtitle="Apex" />
        <b-row>
            <b-col xl="6">
                <UICard title="Basic RadialBar Chart">
                    <ApexChart id="basic-radialbar" class="apex-charts" :chart="basicRadialBarChart" />
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Multiple RadialBars">
                    <ApexChart id="multiple-radialbar" class="apex-charts" :chart="multipleRadialBarsChart" />
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Circle Chart - Custom Angle">
                    <ApexChart id="circle-angle-radial" class="apex-charts" :chart="customAngleChart" />
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Circle Chart with Image">
                    <ApexChart id="image-radial" class="apex-charts" :chart="circleWithImageChart" />
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Stroked Circular Guage">
                    <ApexChart id="stroked-guage-radial" class="apex-charts" :chart="strokedCircularGuageChart" />
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Gradient Circular Chart">
                    <ApexChart id="gradient-chart" class="apex-charts" :chart="gradientCircularChart" />
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Semi Circle Gauge">
                    <ApexChart id="semi-circle-gauge" class="apex-charts" :chart="semiCircleGaugeChart" />
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
import {
    basicRadialBarChart,
    circleWithImageChart,
    customAngleChart,
    gradientCircularChart,
    multipleRadialBarsChart,
    semiCircleGaugeChart,
    strokedCircularGuageChart,
} from './data';
</script>
