<template>
    <VLocaleProvider :rtl="configStore.isAppRTL">
        <!-- ℹ️ This is required to set the background color of active nav link based on currently active global theme's primary -->
        <VApp :style="`--v-global-theme-primary: ${hexToRgb(global.current.value.colors.primary)}`"
        >
            <RouterView />

            <ScrollToTop />
            <Snackbar />
        </VApp>
    </VLocaleProvider>
</template>

<script setup>
import ScrollToTop from "@core/components/ScrollToTop.vue"
import initCore from "@core/initCore"
import { initConfigStore, useConfigStore } from "@core/stores/config"
import { hexToRgb } from "@core/utils/colorConverter"
import { useTheme } from "vuetify"

const { global } = useTheme()

// ℹ️ Sync current theme with initial loader theme
initCore()
initConfigStore()

const configStore = useConfigStore()

const route = useRoute()
const router = useRouter()

window.route = route
window.router = router

onMounted(() => {
    preloadPages()
})

const preloadPages = () => {
    const pages = import.meta.glob(
        "./pages/**/*.vue",
        { eager: false }, // Do not import eagerly
    )

    for (const path in pages) {
        pages[path]() // Preload each page dynamically
    }
}
</script>
