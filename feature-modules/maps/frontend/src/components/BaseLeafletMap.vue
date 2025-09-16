<template>
    <div class="map-wrapper">
        <div
            ref="mapContainer"
            class="map-container"
            :class="{ 'dark-theme': isDark }"
            :style="{ height }"
        ></div>
        <slot />
    </div>
</template>

<script setup>
import L from "leaflet"
import "leaflet/dist/leaflet.css"
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue"
import { useTheme } from "vuetify"

const props = defineProps({
    center: { type: Array, default: () => [-37.8136, 144.9631] },
    zoom: { type: Number, default: 13 },
    height: { type: String, default: "300px" },
    scrollWheelZoom: { type: Boolean, default: false },
})

const mapContainer = ref(null)
let mapInstance = null
const theme = useTheme()
const isDark = computed(() => theme.global.current.value.dark)

const initMap = () => {
    if (!mapContainer.value) return
    mapInstance = L.map(mapContainer.value, {
        center: props.center,
        zoom: props.zoom,
        scrollWheelZoom: props.scrollWheelZoom,
        attributionControl: false,
    })
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: false,
    }).addTo(mapInstance)
}

const getMap = () => mapInstance

defineExpose({ getMap })

onMounted(initMap)

onBeforeUnmount(() => {
    if (mapInstance) {
        mapInstance.remove()
        mapInstance = null
    }
})

watch(
    () => props.center,
    val => {
        if (mapInstance && val && val.length === 2) {
            mapInstance.setView(val)
        }
    },
)

watch(isDark, val => {
    // Ensure the class is applied/removed even if Vue class binding misses timing with Leaflet init
    if (mapContainer.value) {
        mapContainer.value.classList.toggle("dark-theme", !!val)
    }
})
</script>

<style>
.map-container {
    background: rgba(58, 60, 84, 0);
    height: 100%;
    width: 100%;
}

.dark-theme .leaflet-layer,
.dark-theme .leaflet-control-zoom-in,
.dark-theme .leaflet-control-zoom-out,
.dark-theme .leaflet-control-attribution {
    filter: invert(100%) hue-rotate(180deg) brightness(70%) contrast(70%);
}
</style>
