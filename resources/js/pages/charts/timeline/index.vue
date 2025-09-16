<template>
    <VerticalLayout>
        <PageTitle title="Timeline Charts" subtitle="Apex" />
        <b-row>
            <b-col xl="6">
                <UICard title="Basic Timeline">
                    <ApexChart id="basic-timeline" class="apex-charts" :chart="basicTimelineChart" />
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Distributed Timeline">
                    <ApexChart id="distributed-timeline" class="apex-charts" :chart="distributedTimelineChart" />
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Multi Series Timeline">
                    <ApexChart id="multi-series-timeline" class="apex-charts" :chart="multiSeriesTimelineChart" />
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Advanced Timeline">
                    <ApexChart id="advanced-timeline" class="apex-charts" :chart="advancedTimelineChart" />
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Multiple Series - Group Rows">
                    <ApexChart id="group-rows-timeline" class="apex-charts" :chart="groupRowsTimelineChart" />
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
    advancedTimelineChart,
    basicTimelineChart,
    distributedTimelineChart,
    groupRowsTimelineChart,
    multiSeriesTimelineChart,
} from '@/pages/charts/timeline/data';
</script>
