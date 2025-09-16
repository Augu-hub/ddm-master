<template>
    <VerticalLayout>
        <PageTitle title="Candlestick Charts" subtitle="Apex" />
        <b-row>
            <b-col xl="6">
                <UICard title="Simple Candlestick Chart">
                    <div dir="ltr">
                        <ApexChart id="simple-candlestick" class="apex-charts" :chart="simpleCandlestickChart" />
                    </div>
                </UICard>
            </b-col>
            <b-col xl="6">
                <UICard title="Category X-Axis">
                    <div dir="ltr">
                        <ApexChart id="x-axis-candlestick" class="apex-charts" :chart="categoryXAxisChart" />
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Candlestick with Line">
                    <div dir="ltr">
                        <ApexChart id="candlestick-with-line" class="apex-charts" :chart="candlestickWithLineChart" />
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
import { candlestickWithLineChart, categoryXAxisChart, simpleCandlestickChart } from '@/pages/charts/candlestick/data';
</script>
