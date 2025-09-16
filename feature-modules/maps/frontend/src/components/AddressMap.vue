<template>
    <VCard variant="outlined">
        <BaseLeafletMap
            ref="baseRef"
            :center="[lat, lng]"
            :zoom="15"
            :height="height"
        >
            <!-- Marker is added programmatically -->
        </BaseLeafletMap>
    </VCard>
</template>

<script setup>
import L from "leaflet"
import { onMounted, ref, watch } from "vue"
import BaseLeafletMap from "./BaseLeafletMap.vue"

const props = defineProps({
    data: { type: Object, default: () => ({}) },
    lat: { type: [String, Number], default: -37.810272 },
    lng: { type: [String, Number], default: 144.962646 },
    height: { type: String, default: "300px" },
})

let marker
let map
const baseRef = ref(null)

const addOrMoveMarker = () => {
    if (!map) return
    const position = [Number(props.lat), Number(props.lng)]
    if (!marker) {
        marker = L.marker(position, { icon: getDefaultIcon() }).addTo(map)
        marker.bindPopup("Location based on provided coordinates.")
    } else {
        marker.setLatLng(position)
        map.panTo(position)
    }
}

const getDefaultIcon = () => {
    // Use a simple div icon to align with dark theme handling
    return L.divIcon({
        className: "custom-pin",
        html: '<div style="width:14px;height:14px;border-radius:50%;background:#1976d2;border:2px solid #fff;"></div>',
        iconSize: [14, 14],
        iconAnchor: [7, 7],
    })
}

onMounted(() => {
    map = baseRef.value?.getMap?.()
    addOrMoveMarker()
})

watch(
    () => [props.lat, props.lng],
    () => addOrMoveMarker(),
)
</script>
