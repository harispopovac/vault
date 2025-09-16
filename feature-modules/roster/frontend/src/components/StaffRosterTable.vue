<template>
    <VCard>
        <VCardText class="d-flex align-center justify-space-between">
            <h4 class="text-h4">
                <slot name="title">Staff Roster</slot>
            </h4>

            <div class="d-flex align-center gap-4">
                <!-- Site selector -->
                <VSelect
                    v-if="!siteId"
                    v-model="selectedSiteId"
                    :items="sites"
                    item-title="label"
                    item-value="id"
                    placeholder="Select Site"
                    style="min-width: 200px"
                />

                <!-- Search -->
                <VTextField
                    v-model="searchQuery"
                    placeholder="Search staff..."
                    prepend-inner-icon="tabler-search"
                    style="min-width: 200px"
                />

                <!-- Custom actions slot -->
                <slot
                    name="actions"
                    :loading="loading"
                    :rosters="rosters"
                    :reload="loadRosters"
                >
                    <VBtn
                        prepend-icon="tabler-refresh"
                        :loading="loading"
                        @click="loadRosters"
                    >
                        Refresh
                    </VBtn>
                    <VBtn
                        v-if="!readonly"
                        prepend-icon="tabler-plus"
                        color="primary"
                        @click="openStoreDialog"
                    >
                        Add Roster
                    </VBtn>
                </slot>
            </div>
        </VCardText>

        <VDivider />

        <VDataTable
            :headers="headers"
            :items="filteredRosters"
            :loading="loading"
            no-data-text="No roster entries found"
            class="text-no-wrap"
            :items-per-page="itemsPerPage"
            :page="page"
            @update:page="page = $event"
            @update:items-per-page="itemsPerPage = $event"
        >
            <!-- Staff Column -->
            <template #item.staff="{ item }">
                <div class="d-flex align-center gap-3">
                    <VAvatar size="32" color="primary" variant="tonal">
                        <span class="text-sm font-weight-medium">
                            {{ getStaffInitials(item.full_name) }}
                        </span>
                    </VAvatar>
                    <div>
                        <h6 class="text-h6">{{ item.full_name }}</h6>
                        <span class="text-body-2 text-medium-emphasis">{{
                            item.site_name
                        }}</span>
                    </div>
                </div>
            </template>

            <!-- Date Column -->
            <template #item.date="{ item }">
                <div>
                    <span class="text-body-1 font-weight-medium">
                        {{ formatDate(item.rostered_start_date) }}
                    </span>
                    <br />
                    <span class="text-body-2 text-medium-emphasis">
                        {{ getDayName(item.rostered_start_date) }}
                    </span>
                </div>
            </template>

            <!-- Time Column -->
            <template #item.time="{ item }">
                <div>
                    <VChip color="primary" variant="tonal" size="small">
                        {{ formatTime(item.rostered_start_time) }} -
                        {{ formatTime(item.rostered_end_time) }}
                    </VChip>
                    <br />
                    <span class="text-body-2 text-medium-emphasis">
                        {{
                            calculateDuration(
                                item.rostered_start,
                                item.rostered_end,
                            )
                        }}
                    </span>
                </div>
            </template>

            <!-- Status Column -->
            <template #item.status="{ item }">
                <VChip
                    :color="getStatusColor(item)"
                    variant="tonal"
                    size="small"
                >
                    {{ getStatusText(item) }}
                </VChip>
            </template>

            <!-- Rates Column -->
            <template #item.rates="{ item }">
                <div v-if="item.hourlyrate || item.shiftrate">
                    <div v-if="item.hourlyrate" class="text-body-2">
                        Hourly: ${{ item.hourlyrate }}
                    </div>
                    <div v-if="item.shiftrate" class="text-body-2">
                        Shift: ${{ item.shiftrate }}
                    </div>
                </div>
                <span v-else class="text-medium-emphasis">-</span>
            </template>

            <!-- Actions Column -->
            <template #item.actions="{ item }">
                <slot
                    name="row-actions"
                    :item="item"
                    :store-roster="storeRoster"
                    :update-roster="updateRoster"
                    :delete-roster="deleteRoster"
                >
                    <VBtn
                        icon="tabler-dots-vertical"
                        variant="text"
                        color="default"
                        size="x-small"
                    >
                        <VIcon icon="tabler-dots-vertical" />
                        <VMenu activator="parent">
                            <VList>
                                <VListItem @click="updateRoster(item)">
                                    <template #prepend>
                                        <VIcon icon="tabler-edit" size="16" />
                                    </template>
                                    <VListItemTitle>Update</VListItemTitle>
                                </VListItem>

                                <VDivider />

                                <VListItem
                                    class="text-error"
                                    @click="deleteRoster(item)"
                                >
                                    <template #prepend>
                                        <VIcon icon="tabler-trash" size="16" />
                                    </template>
                                    <VListItemTitle>Delete</VListItemTitle>
                                </VListItem>
                            </VList>
                        </VMenu>
                    </VBtn>
                </slot>
            </template>

            <!-- Loading State -->
            <template #loading>
                <div class="text-center pa-6">
                    <VProgressCircular indeterminate color="primary" />
                </div>
            </template>
        </VDataTable>
    </VCard>

    <!-- Store/Update Roster Dialog -->
    <StoreUpdateRosterDialog
        :is-dialog-visible="showStoreUpdateDialog"
        :data="selectedRoster"
        :selected-site-id="actualSiteId"
        :sites="sites"
        @update:is-dialog-visible="showStoreUpdateDialog = false"
        @get-roster="loadRosters"
    />
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import {
    deleteRoster as deleteRosterApi,
    fetchRosters,
} from "../services/staffRosterService"
import StoreUpdateRosterDialog from "./dialogs/StoreUpdateRosterDialog.vue"

// Props
const props = defineProps({
    siteId: {
        type: Number,
        default: null,
    },
    staffIds: {
        type: Array,
        default: () => [],
    },
    readonly: {
        type: Boolean,
        default: false,
    },
    showActions: {
        type: Boolean,
        default: true,
    },
    startDate: {
        type: String,
        default: null,
    },
    endDate: {
        type: String,
        default: null,
    },
    includeOverlapping: {
        type: Boolean,
        default: false,
    },
})

// Emits
const emit = defineEmits([
    "roster-stored",
    "roster-updated",
    "roster-deleted",
    "error",
])

// State
const loading = ref(false)
const rosters = ref([])
const sites = ref([])
const selectedSiteId = ref(props.siteId)
const searchQuery = ref("")
const page = ref(1)
const itemsPerPage = ref(10)

// Dialog state
const showStoreUpdateDialog = ref(false)
const selectedRoster = ref(null)

// Headers
const headers = [
    {
        title: "Staff Member",
        key: "staff",
        sortable: false,
    },
    {
        title: "Date",
        key: "date",
        sortable: false,
    },
    {
        title: "Time",
        key: "time",
        sortable: false,
    },
    {
        title: "Status",
        key: "status",
        sortable: false,
    },
    {
        title: "Rates",
        key: "rates",
        sortable: false,
    },
]

// Add actions column if not readonly
if (!props.readonly && props.showActions) {
    headers.push({
        title: "Actions",
        key: "actions",
        sortable: false,
        align: "end",
    })
}

// Computed
const actualSiteId = computed(() => selectedSiteId.value || props.siteId)

// Computed property for frontend search filtering
const filteredRosters = computed(() => {
    if (!searchQuery.value) {
        return rosters.value
    }
    const query = searchQuery.value.toLowerCase()

    return rosters.value.filter(roster => {
        const staffNameMatch = roster.full_name.toLowerCase().includes(query)
        const siteNameMatch = roster.site_name.toLowerCase().includes(query)

        const dateMatch = formatDate(roster.rostered_start_date)
            .toLowerCase()
            .includes(query)

        const timeMatch = formatTime(roster.rostered_start_time)
            .toLowerCase()
            .includes(query)

        const statusMatch = getStatusText(roster).toLowerCase().includes(query)

        const ratesMatch = (roster.hourlyrate || roster.shiftrate)
            .toString()
            .toLowerCase()
            .includes(query)

        return (
            staffNameMatch ||
            siteNameMatch ||
            dateMatch ||
            timeMatch ||
            statusMatch ||
            ratesMatch
        )
    })
})

// Methods
const loadRosters = async () => {
    if (!actualSiteId.value) return

    loading.value = true
    try {
        const params = {
            site_id: actualSiteId.value,
            start: props.startDate,
            end: props.endDate,
            staff_ids: props.staffIds.length
                ? props.staffIds.join(",")
                : undefined,
            include_overlapping: props.includeOverlapping,
        }

        const response = await fetchRosters(params)

        rosters.value = response.data || []
    } catch (error) {
        console.error("Failed to load rosters:", error)
        rosters.value = []
    } finally {
        loading.value = false
    }
}

const getStaffInitials = name => {
    if (!name) return "?"

    return name
        .split(" ")
        .map(n => n.charAt(0))
        .join("")
        .toUpperCase()
}

const formatDate = date => {
    if (!date) return ""

    return new Date(date).toLocaleDateString("en-AU", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    })
}

const getDayName = date => {
    if (!date) return ""

    return new Date(date).toLocaleDateString("en-AU", { weekday: "long" })
}

const formatTime = time => {
    if (!time) return ""

    return time.slice(0, 5) // HH:mm
}

const calculateDuration = (start, end) => {
    if (!start || !end) return ""
    const startTime = new Date(start)
    const endTime = new Date(end)
    const diff = endTime - startTime
    const hours = Math.floor(diff / (1000 * 60 * 60))
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))

    return `${hours}h ${minutes}m`
}

const getStatusColor = item => {
    if (item.absent) return "error"

    return "success"
}

const getStatusText = item => {
    if (item.absent) return "Absent"

    return "Rostered"
}

const deleteRoster = async item => {
    if (!confirm("Are you sure you want to delete this roster entry?")) return

    try {
        await deleteRosterApi(item.id)
        await loadRosters()
        emit("roster-deleted", item)
    } catch (error) {
        console.error("Failed to delete roster:", error)
    }
}

const openStoreDialog = () => {
    selectedRoster.value = null
    showStoreUpdateDialog.value = true
}

const storeRoster = item => {
    // This method is for slot usage - not directly called
    console.log("Store roster method called:", item)
}

const updateRoster = item => {
    selectedRoster.value = item
    showStoreUpdateDialog.value = true
}

const handleRosterStored = roster => {
    loadRosters()
    emit("roster-stored", roster)
}

const handleRosterUpdated = roster => {
    loadRosters()
    emit("roster-updated", roster)
}

const handleError = error => {
    console.error("Dialog error:", error)
    emit("error", error)
}

// Watch for changes
watch(actualSiteId, () => {
    loadRosters()
})

// Lifecycle
onMounted(() => {
    loadRosters()
})
</script>

<style scoped>
.text-no-wrap {
    white-space: nowrap;
}
</style>
