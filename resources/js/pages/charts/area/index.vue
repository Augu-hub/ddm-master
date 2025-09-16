<template>
    <VerticalLayout>
        <PageTitle title="Area Charts" subtitle="Apex" />
        <b-row>
            <b-col xl="6">
                <UICard title="Basic Area Chart">
                    <div dir="ltr">
                        <ApexChart id="basic-area" class="apex-charts" :chart="basicAreaChart" />
                    </div>
                </UICard>
            </b-col>
            <b-col xl="6">
                <UICard title="Spline Area">
                    <div dir="ltr">
                        <ApexChart id="spline-area" class="apex-charts" :chart="splineAreaChart" />
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Area Chart - Datetime X-axis">
                    <div class="toolbar apex-toolbar">
                        <b-button variant="light" size="sm" id="one_month">1M</b-button>{{ ' ' }}
                        <b-button variant="light" size="sm" id="six_months">6M</b-button>{{ ' ' }}
                        <b-button variant="light" size="sm" id="one_year" active>1Y</b-button>{{ ' ' }}
                        <b-button variant="light" size="sm" id="ytd">YTD</b-button>{{ ' ' }}
                        <b-button variant="light" size="sm" id="all">ALL</b-button>
                    </div>
                    <div dir="ltr">
                        <ApexChart id="area-chart-datetime" class="apex-charts" :chart="datetimeChart" />
                    </div>
                </UICard>
            </b-col>
            <b-col xl="6">
                <UICard title="Area with Negative Values">
                    <div dir="ltr">
                        <ApexChart id="area-chart-negative" class="apex-charts" :chart="negativeValuesChart" />
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Stacked Area">
                    <div dir="ltr">
                        <ApexChart id="stacked-area" class="apex-charts" :chart="stackedAreaChart" />
                        <div id="stacked-area" class="apex-charts" data-colors="#727cf5,#0acf97,#e3eaef"></div>
                    </div>
                </UICard>
            </b-col>
            <b-col xl="6">
                <UICard title="Irregular TimeSeries">
                    <div dir="ltr">
                        <ApexChart id="area-timeSeries" class="apex-charts" :chart="irregularTimeSeriesChart" />
                        <div id="area-timeSeries" class="apex-charts" data-colors="#39afd1,#fa5c7c,#727cf5"></div>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Area Chart with Null values">
                    <div dir="ltr">
                        <ApexChart id="area-chart-nullvalues" class="apex-charts" :chart="nullValuesChart" />
                        <div id="area-chart-nullvalues" class="apex-charts" data-colors="#39afd1"></div>
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
    basicAreaChart,
    datetimeChart,
    irregularTimeSeriesChart,
    negativeValuesChart,
    nullValuesChart,
    splineAreaChart,
    stackedAreaChart,
} from '@/pages/charts/area/data';
</script>
