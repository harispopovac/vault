<template>
    <VContainer fluid>
        <VRow>
            <VCol cols="12">
                <RosterCalendar
                    @roster-loaded="handleRosterLoaded"
                    @error="handleError"
                />
            </VCol>
        </VRow>
    </VContainer>
</template>

<script setup>
import { RosterCalendar } from "staff-roster-module-frontend"
import { ref } from "vue"

// Mock sites data (in real app, this would come from API)
const sites = ref([
    { id: 1, name: "Site 1" },
    { id: 2, name: "Site 2" },
    { id: 3, name: "Site 3" },
])

// Handle roster loaded
const handleRosterLoaded = rosters => {
    console.log("Roster loaded:", rosters)
}

const handleError = error => {
    console.error("Roster error:", error)
}

definePage({
    name: "StaffRoster",
    meta: {
        auth: true,
        layout: "default",
    },
})
</script>
