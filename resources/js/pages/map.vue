<template>
    <div>
        <div class="text-subtitle-1 mb-2">Base Map</div>
        <BaseLeafletMap class="mb-6" />

        <div class="text-subtitle-1 mb-2">Address Map</div>
        <AddressMap
            class="mb-6"
            :lat="-37.8136"
            :lng="144.9631"
            height="400px"
        />

        <div class="text-subtitle-1 mb-2">User Map View</div>
        <UserMapView class="mb-2" :users="mockUsers" :sites="mockSites" />
    </div>
</template>

<script setup>
import { AddressMap, BaseLeafletMap, UserMapView } from "maps-module-frontend"
import { ref } from "vue"

definePage({
    name: "Map",
    meta: {
        auth: true,
        layout: "default",
        title: "Map",
        description: "Map",
    },
})

// Mock data to satisfy required props and avoid runtime errors
const mockUsers = ref([
    { id: 1, lat: -37.814, lng: 144.96332 },
    { id: 2, lat: -37.82, lng: 144.97 },
])

const mockSites = ref([
    { id: 101, name: "Site A", address: { lat: -37.81, lng: 144.96 } },
    { id: 102, name: "Site B", address: { lat: -37.82, lng: 144.98 } },
])
</script>
