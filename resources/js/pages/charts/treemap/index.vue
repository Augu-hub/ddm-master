<template>
    <VerticalLayout>
        <PageTitle title="Treemap Charts" subtitle="Apex" />
        <b-row>
            <b-col xl="6">
                <UICard title="Basic Treemap">
                    <div dir="ltr">
                        <ApexChart id="basic-treemap" class="apex-charts" :chart="basicTreemapChart" />
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Treemap Multiple Series">
                    <div dir="ltr">
                        <ApexChart id="multiple-treemap" class="apex-charts" :chart="multipleSeriesTreemapChart" />
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Distributed Treemap">
                    <div dir="ltr">
                        <ApexChart id="distributed-treemap" class="apex-charts" :chart="distributedTreemapChart" />
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Color Range Treemap">
                    <div dir="ltr">
                        <ApexChart id="color-range-treemap" class="apex-charts" :chart="colorRangeTreemapChart" />
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
import { basicTreemapChart, colorRangeTreemapChart, distributedTreemapChart, multipleSeriesTreemapChart } from '@/pages/charts/treemap/data';
</script>
