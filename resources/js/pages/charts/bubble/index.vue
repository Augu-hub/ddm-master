<template>
    <VerticalLayout>
        <PageTitle title="Bubble Charts" subtitle="Apex" />
        <b-row>
            <b-col xl="6">
                <UICard title="Simple Bubble Chart">
                    <div dir="ltr">
                        <ApexChart id="simple-bubble" class="apex-charts" :chart="simpleBubbleChart" />
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="3D Bubble Chart">
                    <div dir="ltr">
                        <ApexChart id="second-bubble" class="apex-charts" :chart="bubble3DChart" />
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
import { bubble3DChart, simpleBubbleChart } from '@/pages/charts/bubble/data';
</script>
