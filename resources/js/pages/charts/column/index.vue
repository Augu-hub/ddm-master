<template>
    <VerticalLayout>
        <PageTitle title="Column Charts" subtitle="Apex" />

        <b-row>
            <b-col xl="6">
                <UICard title="Basic Column Chart">
                    <div dir="ltr">
                        <ApexChart id="basic-column" class="apex-charts" :chart="basicColumnChart" />
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Column Chart with Datalabels">
                    <div dir="ltr">
                        <ApexChart id="datalabels-column" class="apex-charts" :chart="datalabelsChart" />
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Stacked Column Chart">
                    <div dir="ltr">
                        <ApexChart id="stacked-column" class="apex-charts" :chart="stackedColumnChart" />
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="100% Stacked Column Chart">
                    <div dir="ltr">
                        <ApexChart id="full-stacked-column" class="apex-charts" :chart="fullStackedColumnChart" />
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Column with Markers">
                    <div dir="ltr">
                        <ApexChart id="column-with-markers" class="apex-charts" :chart="markersColumnChart" />
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Column with Group Label">
                    <div dir="ltr">
                        <ApexChart id="column-with-group-label" class="apex-charts" :chart="groupLabelColumnChart" />
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Column Chart with rotated labels & Annotations">
                    <div dir="ltr">
                        <ApexChart id="rotate-labels-column" class="apex-charts" :chart="rotatedLabelsColumnChart" />
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Column Chart with negative values">
                    <div dir="ltr">
                        <ApexChart id="negative-value-column" class="apex-charts" :chart="negativeValuesColumnChart" />
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Distributed Column Chart">
                    <div dir="ltr">
                        <ApexChart id="distributed-column" class="apex-charts" :chart="distributedColumnChart" />
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Range Column Chart">
                    <div dir="ltr">
                        <ApexChart id="range-column" class="apex-charts" :chart="rangeColumnChart" />
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <b-card no-body>
                    <div class="d-flex card-header justify-content-between align-items-center">
                        <h4 class="header-title">Dynamic Loaded Chart</h4>
                        <div class="flex-shrink-0">
                            <select id="model" class="form-select form-select-sm">
                                <option value="iphone5">iPhone 5</option>
                                <option value="iphone6">iPhone 6</option>
                                <option value="iphone7">iPhone 7</option>
                            </select>
                        </div>
                    </div>

                    <b-card-body class="pt-0">
                        <h4 class="header-title"></h4>
                        <div dir="ltr">
                            <b-row>
                                <b-col sm="6">
                                    <ApexChart
                                        :chart="yearChartOptions"
                                        @dataPointSelection="dataPointSelectionHandler"
                                        class="apex-charts"
                                        id="chart-year"
                                    />
                                </b-col>
                                <b-col sm="6">
                                    <ApexChart :chart="quarterChartOptions" class="apex-charts" id="chart-quarter" />
                                </b-col>
                            </b-row>
                        </div>
                    </b-card-body>
                </b-card>
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
    basicColumnChart,
    datalabelsChart,
    dataPointSelectionHandler,
    distributedColumnChart,
    fullStackedColumnChart,
    groupLabelColumnChart,
    markersColumnChart,
    negativeValuesColumnChart,
    quarterChartOptions,
    rangeColumnChart,
    rotatedLabelsColumnChart,
    stackedColumnChart,
    yearChartOptions,
} from '@/pages/charts/column/data';
</script>
