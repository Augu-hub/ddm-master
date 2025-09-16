<template>
    <VerticalLayout>
        <PageTitle title="Leaflet Maps" subtitle="Maps" />
        <b-row>
            <b-col cols="12">
                <UICard title="Basic Map">
                    <div class="leaflet-map" id="basicMap" style="height: 400px"></div>
                </UICard>
            </b-col>

            <b-col cols="12">
                <UICard title="Marker Circle & Polygon">
                    <div class="leaflet-map" id="shapeMap" style="height: 400px"></div>
                </UICard>
            </b-col>

            <b-col cols="12">
                <UICard title="Draggable Marker With Popup">
                    <div class="leaflet-map" id="dragMap" style="height: 400px"></div>
                </UICard>
            </b-col>

            <b-col cols="12">
                <UICard title="User Location">
                    <div class="leaflet-map" id="userLocation" style="height: 400px"></div>
                </UICard>
            </b-col>

            <b-col cols="12">
                <UICard title="Custom Icons">
                    <div class="leaflet-map" id="customIcons" style="height: 400px"></div>
                </UICard>
            </b-col>

            <b-col cols="12">
                <UICard title="Layer Control">
                    <div class="leaflet-map" id="layerControl" style="height: 400px"></div>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import leafGreen from '@/images/png/leaf-green.png';
import leafOrange from '@/images/png/leaf-orange.png';
import leafRed from '@/images/png/leaf-red.png';
import leafShadow from '@/images/png/leaf-shadow.png';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import L from 'leaflet';
import { onMounted } from 'vue';

onMounted(() => {
    // Basic
    const basicMapVar = document.getElementById('basicMap');
    if (basicMapVar) {
        const basicMap = L.map('basicMap').setView([42.35, -71.08], 10);
        L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png', {
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>',
            maxZoom: 18,
        }).addTo(basicMap);
    }

    // Markers
    const shapeMapVar = document.getElementById('shapeMap');
    if (shapeMapVar) {
        const markerMap = L.map('shapeMap').setView([51.5, -0.09], 12);
        const marker = L.marker([51.5, -0.09]).addTo(markerMap);
        const circle = L.circle([51.508, -0.11], {
            color: 'red',
            fillColor: '#f03',
            fillOpacity: 0.5,
            radius: 500,
        }).addTo(markerMap);
        const polygon = L.polygon([
            [51.509, -0.08],
            [51.503, -0.06],
            [51.51, -0.047],
        ]).addTo(markerMap);
        L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png', {
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>',
            maxZoom: 18,
        }).addTo(markerMap);
    }

    // Drag and popup
    const dragMapVar = document.getElementById('dragMap');
    if (dragMapVar) {
        const draggableMap = L.map('dragMap').setView([48.817152, 2.455], 12);
        const markerLocation = L.marker([48.817152, 2.455], {
            draggable: true,
        }).addTo(draggableMap);
        markerLocation.bindPopup("<b>You're here!</b>").openPopup();
        L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png', {
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>',
            maxZoom: 18,
        }).addTo(draggableMap);
    }

    // User location
    const userLocationVar = document.getElementById('userLocation');
    if (userLocationVar) {
        const userLocation = L.map('userLocation').setView([42.35, -71.08], 10);
        userLocation.locate({
            setView: true,
            maxZoom: 16,
        });

        function onLocationFound(e: any) {
            const radius = e.accuracy;
            L.marker(e.latlng)
                .addTo(userLocation)
                .bindPopup('You are somewhere around ' + radius + ' meters from this point')
                .openPopup();
            L.circle(e.latlng, radius).addTo(userLocation);
        }

        userLocation.on('locationfound', onLocationFound);
        L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png', {
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>',
            maxZoom: 18,
        }).addTo(userLocation);
    }

    // Custom Icons
    const customIconsVar = document.getElementById('customIcons');
    if (customIconsVar) {
        const customIcons = L.map('customIcons').setView([51.5, -0.09], 10);
        const greenLeaf = L.icon({
            iconUrl: leafGreen,
            shadowUrl: leafShadow,
            iconSize: [38, 95],
            shadowSize: [50, 64],
            iconAnchor: [22, 94],
            shadowAnchor: [4, 62],
            popupAnchor: [-3, -76],
        });
        const redLeaf = L.icon({
            iconUrl: leafRed,
            shadowUrl: leafShadow,
            iconSize: [38, 95],
            shadowSize: [50, 64],
            iconAnchor: [22, 94],
            shadowAnchor: [4, 62],
            popupAnchor: [-3, -76],
        });
        const orangeLeaf = L.icon({
            iconUrl: leafOrange,
            shadowUrl: leafShadow,
            iconSize: [38, 95],
            shadowSize: [50, 64],
            iconAnchor: [22, 94],
            shadowAnchor: [4, 62],
            popupAnchor: [-3, -76],
        });
        L.marker([51.5, -0.09], {
            icon: redLeaf,
        }).addTo(customIcons);
        L.marker([51.4, -0.51], {
            icon: greenLeaf,
        }).addTo(customIcons);
        L.marker([51.49, -0.45], {
            icon: orangeLeaf,
        }).addTo(customIcons);
        L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png', {
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>',
            maxZoom: 18,
        }).addTo(customIcons);
    }

    // Layer Control
    const layerControlVar = document.getElementById('layerControl');
    if (layerControlVar) {
        const littleton = L.marker([39.61, -105.02]).bindPopup('This is Littleton, CO.'),
            denver = L.marker([39.74, -104.99]).bindPopup('This is Denver, CO.'),
            aurora = L.marker([39.73, -104.8]).bindPopup('This is Aurora, CO.'),
            golden = L.marker([39.77, -105.23]).bindPopup('This is Golden, CO.');
        const cities = L.layerGroup([littleton, denver, aurora, golden]);
        const street = L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png', {
                attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>',
                maxZoom: 18,
            }),
            watercolor = L.tileLayer('http://tile.stamen.com/watercolor/{z}/{x}/{y}.jpg', {
                attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>',
                maxZoom: 18,
            });
        const layerControl = L.map('layerControl', {
            center: [39.73, -104.99],
            zoom: 10,
            layers: [street, cities],
        });
        const baseMaps = {
            Street: street,
            Watercolor: watercolor,
        };
        const overlayMaps = {
            Cities: cities,
        };
        L.control.layers(baseMaps, overlayMaps).addTo(layerControl);
        L.tileLayer('https://c.tile.osm.org/{z}/{x}/{y}.png', {
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>',
            maxZoom: 18,
        }).addTo(layerControl);
    }
});
</script>
