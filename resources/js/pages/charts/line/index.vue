<template>
    <VerticalLayout>
        <PageTitle title="Line Charts" subtitle="Apex" />
        <b-row>
            <b-col xl="6">
                <UICard title="Simple line chart">
                    <div dir="ltr">
                        <ApexChart id="line-chart" class="apex-charts" :chart="simpleLineChart" />
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Line with Data Labels">
                    <div dir="ltr">
                        <ApexChart id="line-chart-datalabel" class="apex-charts" :chart="dataLabelsChart" />
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Zoomable Timeseries">
                    <div dir="ltr">
                        <ApexChart id="line-chart-zoomable" class="apex-charts" :chart="zoomableTimeseries" />
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Line Chart with Annotations">
                    <div dir="ltr">
                        <ApexChart id="line-chart-annotations" class="apex-charts" :chart="lineChartWithAnnotation" />
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Syncing charts">
                    <ApexChart id="line-chart-syncing2" class="apex-charts" :chart="syncingChart" />
                    <div dir="ltr">
                        <ApexChart id="line-chart-syncing" class="apex-charts" :chart="syncingChart2" />
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Gradient Line Chart">
                    <div dir="ltr">
                        <ApexChart id="line-chart-gradient" class="apex-charts" :chart="gradientLineChart" />
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Missing / Null values">
                    <div dir="ltr">
                        <ApexChart id="line-chart-missing" class="apex-charts" :chart="missingChart" />
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Dashed Line Chart">
                    <div dir="ltr">
                        <ApexChart id="line-chart-dashed" class="apex-charts" :chart="dashedLineChart" />
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Stepline Chart">
                    <div dir="ltr">
                        <ApexChart id="line-chart-stepline" class="apex-charts" :chart="steplineChart" />
                    </div>
                </UICard>
            </b-col>
            <b-col xl="6">
                <UICard title="Brush Chart">
                    <div dir="ltr">
                        <ApexChart :chart="brushChart" class="apex-charts" id="chart-line" />
                        <ApexChart :chart="brushChart2" class="apex-charts" id="chart-line2" />
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
import {
    brushChart,
    brushChart2,
    dashedLineChart,
    dataLabelsChart,
    gradientLineChart,
    lineChartWithAnnotation,
    missingChart,
    simpleLineChart,
    steplineChart,
    syncingChart,
    syncingChart2,
    zoomableTimeseries,
} from '@/pages/charts/line/data';
</script>
