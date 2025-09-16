<template>
    <VerticalLayout>
        <PageTitle title="Bar Charts" subtitle="Apex" />
        <b-row>
            <b-col xl="6">
                <UICard title="Basic Bar Chart">
                    <div dir="ltr">
                        <ApexChart id="basic-bar" class="apex-charts" :chart="basicBarChart" />
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Grouped Bar Chart">
                    <div dir="ltr">
                        <ApexChart id="grouped-bar" class="apex-charts" :chart="groupedBarChart" />
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Stacked Bar Chart">
                    <div dir="ltr">
                        <ApexChart id="stacked-bar" class="apex-charts" :chart="stackedBarChart" />
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="100% Stacked Bar Chart">
                    <div dir="ltr">
                        <ApexChart id="full-stacked-bar" class="apex-charts" :chart="fullStackedBarChart" />
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Bar with Negative Values">
                    <div dir="ltr">
                        <ApexChart id="negative-bar" class="apex-charts" :chart="negativeBarChart" />
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Reversed Bar Chart">
                    <div dir="ltr">
                        <ApexChart id="reversed-bar" class="apex-charts" :chart="reversedBarChart" />
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Bar with Image Fill">
                    <div dir="ltr">
                        <ApexChart id="image-fill-bar" class="apex-charts" :chart="imageFillBarChart" />
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Custom DataLabels Bar">
                    <div dir="ltr">
                        <ApexChart id="datalables-bar" class="apex-charts" :chart="customDataLabelsBarChart" />
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Patterned Bar Chart">
                    <div dir="ltr">
                        <ApexChart id="pattern-bar" class="apex-charts" :chart="patternedBarChart" />
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Bar with Markers">
                    <div dir="ltr">
                        <ApexChart id="bar-markers" class="apex-charts" :chart="markersBarChart" />
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
    basicBarChart,
    customDataLabelsBarChart,
    fullStackedBarChart,
    groupedBarChart,
    imageFillBarChart,
    markersBarChart,
    negativeBarChart,
    patternedBarChart,
    reversedBarChart,
    stackedBarChart,
} from '@/pages/charts/bar/data';
</script>
