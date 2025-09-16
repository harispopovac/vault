<template>
    <div class="user-map">
        <VCard variant="outlined">
            <div
                ref="mapContainer"
                class="map-container"
                :style="{ height: isWidget ? '400px' : '800px' }"
            ></div>
        </VCard>
    </div>
</template>

<script setup>
import L from "leaflet"
import "leaflet/dist/leaflet.css"
import { onBeforeUnmount, onMounted, ref, watch } from "vue"

const props = defineProps({
    users: { type: Array, required: true },
    sites: { type: Array, required: true },
    isWidget: { type: Boolean, default: false },
})

const mapContainer = ref(null)
const map = ref(null)
const markers = ref([])
const siteMarkers = ref([])

const initMap = () => {
    if (!mapContainer.value) return
    if (map.value) map.value.remove()
    map.value = L.map(mapContainer.value, {
        scrollWheelZoom: true,
        zoomControl: true,
        attributionControl: false,
    }).setView([-37.8136, 144.9631], 8)

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: false,
    }).addTo(map.value)

    addUserMarkers()
    addSiteMarkers()
}

const addUserMarkers = () => {
    if (markers.value.length) {
        markers.value.forEach(m => map.value.removeLayer(m))
        markers.value = []
    }

    const usersWithCoords = props.users.filter(
        u =>
            u.lat &&
            u.lng &&
            !isNaN(parseFloat(u.lat)) &&
            !isNaN(parseFloat(u.lng)),
    )

    usersWithCoords.forEach(user => {
        const lat = parseFloat(user.lat)
        const lng = parseFloat(user.lng)

        const circle = L.circleMarker([lat, lng], {
            radius: 6,
            fillColor: "#1976d2",
            color: "#ffffff",
            weight: 1,
            opacity: 1,
            fillOpacity: 1,
        }).addTo(map.value)

        markers.value.push(circle)
    })
    if (markers.value.length > 0) {
        const group = L.featureGroup(markers.value)

        map.value.fitBounds(group.getBounds().pad(0.1))
    }
}

const addSiteMarkers = () => {
    if (siteMarkers.value.length) {
        siteMarkers.value.forEach(m => map.value.removeLayer(m))
        siteMarkers.value = []
    }

    const sitesWithCoords = props.sites.filter(
        s =>
            s.address?.lat &&
            s.address?.lng &&
            !isNaN(parseFloat(s.address.lat)) &&
            !isNaN(parseFloat(s.address.lng)),
    )

    sitesWithCoords.forEach(site => {
        const lat = parseFloat(site.address.lat)
        const lng = parseFloat(site.address.lng)

        const circle = L.circleMarker([lat, lng], {
            radius: 10,
            fillColor: "#FFD700",
            color: "#FF0000",
            weight: 2,
            opacity: 1,
            fillOpacity: 0.8,
        }).addTo(map.value)

        siteMarkers.value.push(circle)
    })
}

watch(
    () => props.users,
    () => {
        if (map.value) {
            addUserMarkers()
        }
    },
    { deep: true },
)

watch(
    () => props.sites,
    () => {
        if (map.value) addSiteMarkers()
    },
    { deep: true },
)

onMounted(initMap)
onBeforeUnmount(() => {
    if (map.value) {
        map.value.remove()
        map.value = null
    }
})
</script>

<style scoped>
.map-container {
    width: 100%;
}
</style>
