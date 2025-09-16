<template>
    <VerticalLayout>
        <PageTitle title="Google Maps" subtitle="Maps" />
        <b-row>
            <b-col xl="6">
                <UICard title="Basic Google Map">
                    <GoogleMap :api-key="api" :center="center" :zoom="5" class="gmaps"></GoogleMap>
                </UICard>
            </b-col>
            <b-col xl="6">
                <UICard title="Markers Google Map">
                    <GoogleMap :api-key="api" :center="center" :zoom="3" class="gmaps">
                        <Marker v-for="(m, index) in markers" :key="index" :options="m.markerOptions" :clickable="true" :draggable="true"></Marker>
                    </GoogleMap>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Polygon Editing">
                    <GoogleMap :api-key="api" :center="{ lat: 24.886, lng: -70.268 }" :zoom="4" class="gmaps">
                        <Polygon :options="polygonOption" :editable="true"></Polygon>
                    </GoogleMap>
                </UICard>
            </b-col>
            <b-col xl="6">
                <UICard title="Info Window">
                    <GoogleMap :api-key="api" :center="center" :zoom="10" class="gmaps">
                        <InfoWindow :options="{ position: center, content: 'Hello viewer!' }" />
                    </GoogleMap>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Heatmap Layer">
                    <GoogleMap
                        :api-key="api"
                        :libraries="['visualization']"
                        style="height: 300px"
                        :center="{ lat: 37.774546, lng: -122.433523 }"
                        :zoom="13"
                    >
                        <HeatmapLayer :options="heatmapData" />
                    </GoogleMap>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { ref } from 'vue';

import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import 'https://maps.googleapis.com/maps/api/js';
import { GoogleMap, HeatmapLayer, InfoWindow, Marker, Polygon } from 'vue3-google-map';

const api = ref('');
const center = ref({ lat: -12.043333, lng: -77.028333 });
const markers = ref([{ markerOptions: { position: { lat: -12.04, lng: -77.0 }, label: 'Hi', title: 'Hello from marker' } }]);

const polygonOption = {
    paths: [
        { lat: 25.774, lng: -80.19 },
        { lat: 18.466, lng: -66.118 },
        { lat: 32.321, lng: -64.757 },
        { lat: 25.774, lng: -80.19 },
    ],
    strokeColor: '#FF0000',
    strokeOpacity: 0.8,
    strokeWeight: 2,
    fillColor: '#FF0000',
    fillOpacity: 0.35,
};
const heatmapData = {
    data: [
        { location: { lat: 37.782, lng: -122.447 }, weight: 0.5 },
        { lat: 37.782, lng: -122.445 },
        { location: { lat: 37.782, lng: -122.443 }, weight: 2 },
        { location: { lat: 37.782, lng: -122.441 }, weight: 3 },
        { location: { lat: 37.782, lng: -122.439 }, weight: 2 },
        { lat: 37.782, lng: -122.437 },
        { location: { lat: 37.782, lng: -122.435 }, weight: 0.5 },

        { location: { lat: 37.785, lng: -122.447 }, weight: 3 },
        { location: { lat: 37.785, lng: -122.445 }, weight: 2 },
        { lat: 37.785, lng: -122.443 },
        { location: { lat: 37.785, lng: -122.441 }, weight: 0.5 },
        { lat: 37.785, lng: -122.439 },
        { location: { lat: 37.785, lng: -122.437 }, weight: 2 },
        { location: { lat: 37.785, lng: -122.435 }, weight: 3 },
    ],
};
</script>
