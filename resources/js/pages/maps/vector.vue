<template>
    <VerticalLayout>
        <PageTitle title="Vector Maps" subtitle="Maps" />
        <b-row>
            <b-col lg="6">
                <UICard title="World Vector Map">
                    <JsVectorMap id="world-map-markers" :height="360" :options="worldMapOptions" />
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="World Vector Map">
                    <JsVectorMap id="world-map-markers-line" :height="360" :options="worldMapLineOptions" />
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="India Vector Map">
                    <JsVectorMap id="india-vector-map" :height="360" :options="indiaMap" />
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="Canada Vector Map">
                    <JsVectorMap id="canada-vector-map" :height="360" :options="canadaMap" />
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="Russia Vector Map">
                    <JsVectorMap id="russia-vector-map" :height="360" :options="russiaMap" />
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="US Vector Map">
                    <JsVectorMap id="usa-vector-map" :height="360" :options="usMap" />
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="Iraq Vector Map">
                    <JsVectorMap id="iraq-vector-map" :height="360" :options="iraqMap" />
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="Spain Vector Map">
                    <JsVectorMap id="spain-vector-map" :height="360" :options="spainMap" />
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import JsVectorMap from '@/components/JsVectorMap.vue';
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';

const worldMapOptions = {
    map: 'world',
    selector: '#world-map-markers',
    zoomOnScroll: false,
    zoomButtons: true,
    markersSelectable: true,
    markers: [
        { name: 'Greenland', coords: [72, -42] },
        { name: 'Canada', coords: [56.1304, -106.3468] },
        { name: 'Brazil', coords: [-14.235, -51.9253] },
        { name: 'Egypt', coords: [26.8206, 30.8025] },
        { name: 'Russia', coords: [61, 105] },
        { name: 'China', coords: [35.8617, 104.1954] },
        { name: 'United States', coords: [37.0902, -95.7129] },
        { name: 'Norway', coords: [60.472024, 8.468946] },
        { name: 'Ukraine', coords: [48.379433, 31.16558] },
    ],
    markerStyle: {
        initial: { fill: '#3e60d5' },
        selected: { fill: '#3e60d56e' },
    },
    regionStyle: {
        initial: {
            fill: '#9ca3af69',
            fillOpacity: 1,
        },
    },
    labels: {
        markers: {
            render: (marker: any) => marker.name,
        },
    },
};

const worldMapLineOptions = {
    map: 'world_merc',
    selector: '#world-map-markers-line',
    zoomOnScroll: false,
    zoomButtons: false,
    markers: [
        { name: 'Greenland', coords: [72, -42] },
        { name: 'Canada', coords: [56.1304, -106.3468] },
        { name: 'Brazil', coords: [-14.235, -51.9253] },
        { name: 'Egypt', coords: [26.8206, 30.8025] },
        { name: 'Russia', coords: [61, 105] },
        { name: 'China', coords: [35.8617, 104.1954] },
        { name: 'United States', coords: [37.0902, -95.7129] },
        { name: 'Norway', coords: [60.472024, 8.468946] },
        { name: 'Ukraine', coords: [48.379433, 31.16558] },
    ],
    lines: [
        { from: 'Canada', to: 'Egypt' },
        { from: 'Russia', to: 'Egypt' },
        { from: 'Greenland', to: 'Egypt' },
        { from: 'Brazil', to: 'Egypt' },
        { from: 'United States', to: 'Egypt' },
        { from: 'China', to: 'Egypt' },
        { from: 'Norway', to: 'Egypt' },
        { from: 'Ukraine', to: 'Egypt' },
    ],
    regionStyle: {
        initial: {
            stroke: '#9ca3af',
            strokeWidth: 0.25,
            fill: '#9ca3af69',
            fillOpacity: 1,
        },
    },
    markerStyle: {
        initial: { fill: '#9ca3af' },
        selected: { fill: '#9ca3af' },
    },
    lineStyle: {
        animation: true,
        strokeDasharray: '6 3 6',
    },
};

const indiaMap = {
    map: 'in_mill',
    backgroundColor: 'transparent',
    selector: '#india-vector-map',
    regionStyle: {
        initial: {
            fill: '#16a7e9',
        },
    },
};

const canadaMap = {
    map: 'canada',
    selector: '#canada-vector-map',
    zoomOnScroll: false,
    regionStyle: {
        initial: {
            fill: '#3e60d5',
        },
    },
};

const russiaMap = {
    map: 'russia',
    selector: '#russia-vector-map',
    zoomOnScroll: false,
    regionStyle: {
        initial: {
            fill: '#5d7186',
        },
    },
};

const usMap = {
    map: 'us_aea_en',
    selector: '#usa-vector-map',
    regionStyle: {
        initial: {
            fill: '#3e60d5',
        },
    },
};

const iraqMap = {
    map: 'iraq',
    selector: '#iraq-vector-map',
    zoomOnScroll: false,
    regionStyle: {
        initial: {
            fill: '#3e60d5',
        },
    },
};

const spainMap = {
    map: 'spain',
    selector: '#spain-vector-map',
    zoomOnScroll: false,
    regionStyle: {
        initial: {
            fill: '#ffc35a',
        },
    },
};
</script>
