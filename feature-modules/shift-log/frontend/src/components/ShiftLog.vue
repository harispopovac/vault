<template>
    <section>
        <VCard>
            <!-- Header Controls -->
            <VCardTitle
                v-if="$vuetify.display.lgAndUp"
                class="d-flex justify-space-between py-4 gap-4 w-full"
            >
                <!-- Date Navigation -->
                <div class="me-3 d-flex gap-3">
                    <div class="d-flex align-center gap-2">
                        <VBtn
                            icon
                            size="small"
                            variant="text"
                            color="medium-emphasis"
                            @click="goToToday"
                        >
                            <VIcon icon="tabler-home" />
                        </VBtn>
                        <VBtn
                            icon
                            size="small"
                            variant="text"
                            color="medium-emphasis"
                            @click="previousDay"
                        >
                            <VIcon icon="tabler-chevron-left" />
                        </VBtn>
                        <VBtn
                            icon
                            size="small"
                            variant="text"
                            color="medium-emphasis"
                            @click="nextDay"
                        >
                            <VIcon icon="tabler-chevron-right" />
                        </VBtn>
                        <span class="ml-2 date-title">
                            {{ formattedDate }}
                        </span>
                    </div>
                </div>

                <!-- Filters and Actions -->
                <div class="d-flex gap-4">
                    <!-- Site Filter (if enabled) -->
                    <AppSelect
                        v-if="sites.length > 0"
                        v-model="selectedSiteId"
                        :items="sites"
                        item-title="name"
                        item-value="id"
                        placeholder="Select Site"
                        style="min-width: 200px"
                    />

                    <!-- Staff Filter -->
                    <AppAutocomplete
                        v-model="selectedStaffId"
                        v-model:search="staffSearch"
                        :loading="staffLoading"
                        :items="staffList"
                        item-title="fullname"
                        item-value="id"
                        placeholder="Select Staff"
                        style="min-width: 200px"
                        clearable
                    />

                    <!-- Search -->
                    <AppTextField
                        v-model="searchQuery"
                        placeholder="Search"
                        style="min-width: 200px"
                        prepend-inner-icon="tabler-search"
                    />

                    <!-- Add Manual Check-in -->
                    <StoreUpdateShiftLogDialog
                        v-if="can(['manage_shift_log'])"
                        activator-type="button"
                        :data="{}"
                        :selected-site-id="selectedSiteId"
                        @refresh="getShiftLogs"
                    />
                </div>
            </VCardTitle>

            <!-- Mobile/Tablet Layout -->
            <VCardTitle v-else class="py-4 w-full">
                <div class="d-flex flex-column gap-4">
                    <!-- Date and Actions Row -->
                    <div class="d-flex justify-space-between align-center gap-4"
                    >
                        <div class="d-flex gap-2 align-center">
                            <div
                                v-if="$vuetify.display.mdAndUp"
                                class="d-flex align-center gap-1"
                            >
                                <VBtn
                                    icon
                                    size="small"
                                    density="compact"
                                    variant="text"
                                    color="medium-emphasis"
                                    @click="goToToday"
                                >
                                    <VIcon icon="tabler-home" size="small" />
                                </VBtn>
                                <VBtn
                                    icon
                                    size="small"
                                    density="compact"
                                    variant="text"
                                    color="medium-emphasis"
                                    @click="previousDay"
                                >
                                    <VIcon
                                        icon="tabler-chevron-left"
                                        size="small"
                                    />
                                </VBtn>
                                <VBtn
                                    icon
                                    size="small"
                                    density="compact"
                                    variant="text"
                                    color="medium-emphasis"
                                    @click="nextDay"
                                >
                                    <VIcon
                                        icon="tabler-chevron-right"
                                        size="small"
                                    />
                                </VBtn>
                            </div>
                        </div>

                        <StoreUpdateShiftLogDialog
                            v-if="can(['manage_shift_log'])"
                            activator-type="button"
                            :data="{}"
                            :selected-site-id="selectedSiteId"
                            @refresh="getShiftLogs"
                        />
                    </div>

                    <!-- Date Display -->
                    <div class="d-flex align-center justify-start">
                        <span class="date-title">{{ formattedDate }}</span>
                    </div>

                    <!-- Mobile Date Navigation -->
                    <div class="d-flex d-md-none align-center justify-start gap-2"
                    >
                        <VBtn
                            icon
                            size="small"
                            variant="text"
                            color="medium-emphasis"
                            @click="goToToday"
                        >
                            <VIcon icon="tabler-home" />
                        </VBtn>
                        <VBtn
                            icon
                            size="small"
                            variant="text"
                            color="medium-emphasis"
                            @click="previousDay"
                        >
                            <VIcon icon="tabler-chevron-left" />
                        </VBtn>
                        <VBtn
                            icon
                            size="small"
                            variant="text"
                            color="medium-emphasis"
                            @click="nextDay"
                        >
                            <VIcon icon="tabler-chevron-right" />
                        </VBtn>
                    </div>

                    <!-- Filters -->
                    <div class="d-flex flex-column gap-3">
                        <AppTextField
                            v-model="searchQuery"
                            placeholder="Search"
                            prepend-inner-icon="tabler-search"
                        />

                        <AppSelect
                            v-if="sites.length > 0"
                            v-model="selectedSiteId"
                            :items="sites"
                            item-title="name"
                            item-value="id"
                            placeholder="Select Site"
                        />

                        <AppAutocomplete
                            v-model="selectedStaffId"
                            v-model:search="staffSearch"
                            :loading="staffLoading"
                            :items="staffList"
                            item-title="fullname"
                            item-value="id"
                            placeholder="Select Staff"
                            clearable
                        />
                    </div>
                </div>
            </VCardTitle>

            <VDivider />

            <!-- Data Table -->
            <VDataTableServer
                v-model:items-per-page="itemsPerPage"
                v-model:page="page"
                :headers="headers"
                :items="shiftLogs"
                :items-length="totalLogs"
                item-value="id"
                @update:options="updateOptions"
                @click:row="handleRowClick"
            >
                <!-- Staff Member Column -->
                <template #[`item.staff-member`]="{ item }">
                    <div class="d-flex align-center">
                        <div class="d-flex flex-column">
                            <span class="text-body-1 font-weight-medium">
                                {{ item.fname }} {{ item.sname }}
                            </span>
                            <span
                                v-if="props.sites.length > 0 && item.site_name"
                                class="text-caption text-medium-emphasis"
                            >
                                {{ item.site_name }}
                            </span>
                        </div>
                    </div>
                </template>

                <!-- Shift Date Column -->
                <template #[`item.shift-date`]="{ item }">
                    <div class="d-flex align-center">
                        <div class="d-flex flex-column">
                            <span
                                v-if="item.rostered_start || item.claimed_start"
                                class="text-body-1"
                            >
                                {{
                                    formatShiftDate(
                                        item.rostered_start ||
                                            item.claimed_start,
                                    )
                                }}
                            </span>
                            <span v-else class="text-body-1">--</span>

                            <!-- Overtime Indicator -->
                            <div
                                v-if="
                                    shiftLogConfig.enable_overtime &&
                                        item.ot_claim
                                "
                                class="d-flex align-center mt-1"
                            >
                                <VTooltip location="top">
                                    <template #activator="{ props }">
                                        <VIcon
                                            v-bind="props"
                                            color="warning"
                                            icon="tabler-clock-exclamation"
                                            size="16"
                                            class="me-1"
                                        />
                                    </template>
                                    <span
                                    >Overtime Requested:
                                        {{ Math.floor(item.ot_claim / 60) }}h
                                        {{ item.ot_claim % 60 }}m</span
                                    >
                                </VTooltip>
                                <span class="text-caption text-warning">
                                    OT: {{ Math.floor(item.ot_claim / 60) }}h
                                    {{ item.ot_claim % 60 }}m
                                </span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Rostered Time Column -->
                <template #[`item.rostered-time`]="{ item }">
                    <div class="d-flex align-center">
                        <div class="d-flex flex-column">
                            <span
                                v-if="item.rostered_start && item.rostered_end"
                                class="text-body-1"
                            >
                                {{ formatTime(item.rostered_start) }} —
                                {{ formatTime(item.rostered_end) }}
                            </span>
                            <span
                                v-else
                                class="text-body-1 text-medium-emphasis"
                            >
                                Not rostered
                            </span>
                        </div>
                    </div>
                </template>

                <!-- Claimed Time Column -->
                <template #[`item.claimed-time`]="{ item }">
                    <div v-if="item.id">
                        <div class="d-flex align-center">
                            <div class="d-flex flex-column">
                                <!-- Show completed shift -->
                                <span
                                    v-if="
                                        item.claimed_start && item.claimed_end
                                    "
                                    class="text-body-1 d-flex align-center gap-2"
                                >
                                    <span>
                                        {{ formatTime(item.claimed_start) }} —
                                        {{ formatTime(item.claimed_end) }}
                                    </span>
                                    <!-- Late/Early indicators -->
                                    <VTooltip
                                        v-if="getTimeVariance(item)"
                                        location="top"
                                    >
                                        <template #activator="{ props }">
                                            <VIcon
                                                v-bind="props"
                                                :color="
                                                    getTimeVariance(item).color
                                                "
                                                :icon="
                                                    getTimeVariance(item).icon
                                                "
                                                size="16"
                                            />
                                        </template>
                                        <span>{{
                                            getTimeVariance(item).message
                                        }}</span>
                                    </VTooltip>
                                </span>

                                <!-- Show in-progress shift -->
                                <span
                                    v-else-if="
                                        item.claimed_start && !item.claimed_end
                                    "
                                    class="text-body-1 d-flex align-center gap-2"
                                >
                                    <span>
                                        {{ formatTime(item.claimed_start) }} —
                                        <span class="text-warning font-weight-bold"
                                        >In Progress</span
                                        >
                                    </span>
                                    <VTooltip
                                        v-if="getTimeVariance(item)"
                                        location="top"
                                    >
                                        <template #activator="{ props }">
                                            <VIcon
                                                v-bind="props"
                                                :color="
                                                    getTimeVariance(item).color
                                                "
                                                :icon="
                                                    getTimeVariance(item).icon
                                                "
                                                size="16"
                                            />
                                        </template>
                                        <span>{{
                                            getTimeVariance(item).message
                                        }}</span>
                                    </VTooltip>
                                </span>

                                <!-- No times logged -->
                                <span
                                    v-else
                                    class="text-body-1 text-medium-emphasis"
                                >--</span
                                >
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Break Count Column (if enabled) -->
                <template
                    v-if="shiftLogConfig.enable_breaks"
                    #[`item.break-count`]="{ item }"
                >
                    <div v-if="item.breaks_count" class="d-flex align-center">
                        <span class="text-body-1">
                            {{ item.breaks_count }} ({{
                                item.total_break_minutes
                            }}m)
                        </span>
                    </div>
                    <div v-else class="d-flex align-center">
                        <span class="text-body-1 text-medium-emphasis">--</span>
                    </div>
                </template>

                <!-- Actions Column -->
                <template #[`item.actions`]="{ item }">
                    <div
                        v-if="can(['manage_shift_log'])"
                        class="d-flex align-center gap-2"
                    >
                        <StoreUpdateShiftLogDialog
                            v-if="!item.actual_start"
                            activator-type="check-in"
                            :data="item"
                            :selected-site-id="selectedSiteId"
                            :config="shiftLogConfig"
                            @refresh="getShiftLogs"
                        />

                        <StoreUpdateShiftLogDialog
                            v-else-if="!item.actual_end"
                            activator-type="check-out"
                            :data="item"
                            :selected-site-id="selectedSiteId"
                            :config="shiftLogConfig"
                            @refresh="getShiftLogs"
                        />

                        <!-- Completed shift indicator -->
                        <VChip
                            v-else
                            color="success"
                            size="small"
                            variant="tonal"
                        >
                            Completed
                        </VChip>
                    </div>
                </template>

                <!-- Pagination -->
                <template #bottom>
                    <TablePagination
                        v-model:page="page"
                        :items-per-page="itemsPerPage"
                        :total-items="totalLogs"
                        @update:items-per-page="itemsPerPage = $event"
                    />
                </template>
            </VDataTableServer>
        </VCard>

        <!-- Shift Details Dialog -->
        <ShiftLogDetailsDialog
            ref="shiftDetailsDialog"
            :data="selectedShift"
            :config="shiftLogConfig"
            @refresh="getShiftLogs"
        />
    </section>
</template>

<script setup>
import { useSnackbarStore } from "@/stores/snackbar"
import { can } from "@layouts/plugins/casl" // Assuming CASL is available
import dayjs from "dayjs"
import { debounce } from "lodash"
import { computed, defineProps, onMounted, ref, watch } from "vue"
import shiftLogService from "../services/shiftLogService.js"
import ShiftLogDetailsDialog from "./dialogs/ShiftLogDetailsDialog.vue"
import StoreUpdateShiftLogDialog from "./dialogs/StoreUpdateShiftLogDialog.vue"

const props = defineProps({
    sites: {
        type: Array,
        default: () => [],
    },
})

const snackbar = useSnackbarStore()

// Reactive state
const currentDate = ref(new Date())
const shiftLogs = ref([])
const page = ref(1)
const itemsPerPage = ref(100)
const searchQuery = ref("")
const totalLogs = ref(0)

// Configuration (default - can be overridden via props if needed)
const shiftLogConfig = ref({
    enable_sites: true,
    enable_breaks: false,
    enable_overtime: false,
    enable_photos: false,
    enable_comments: true,
    labels: {
        shift: "Shift",
        check_in: "Check In",
        check_out: "Check Out",
        overtime: "Overtime",
        grouping_label: "Site",
    },
})

// Filters

const selectedSiteId = ref(null)
const selectedStaffId = ref(null)
const staffList = ref([])
const staffSearch = ref("")
const staffLoading = ref(false)

// Dialog references
const shiftDetailsDialog = ref(null)
const selectedShift = ref({})

// Table headers - dynamically built based on configuration
const headers = computed(() => {
    const baseHeaders = [
        {
            title: "Staff Member",
            key: "staff-member",
            sortable: false,
        },
        {
            title: "Shift Date",
            key: "shift-date",
            sortable: false,
        },
        {
            title: "Rostered Time",
            key: "rostered-time",
            sortable: false,
        },
        {
            title: "Claimed Time",
            key: "claimed-time",
            sortable: false,
        },
    ]

    // Add break count column if breaks are enabled
    if (shiftLogConfig.value.enable_breaks) {
        baseHeaders.push({
            title: "Break Count",
            key: "break-count",
            sortable: false,
        })
    }

    // Add actions column
    baseHeaders.push({
        title: "",
        key: "actions",
        sortable: false,
        align: "end",
    })

    return baseHeaders
})

// Date formatting
const formattedDate = computed(() => {
    return currentDate.value.toLocaleDateString("en-AU", {
        weekday: "long",
        month: "long",
        day: "numeric",
        year: "numeric",
    })
})

// Helper function to format date for API
const formatDateForAPI = date => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, "0")
    const day = String(date.getDate()).padStart(2, "0")
    
    return `${year}-${month}-${day}`
}

// Date navigation methods
const goToToday = () => {
    currentDate.value = new Date()
}

const previousDay = () => {
    const newDate = new Date(currentDate.value)

    newDate.setDate(newDate.getDate() - 1)
    currentDate.value = newDate
}

const nextDay = () => {
    const newDate = new Date(currentDate.value)

    newDate.setDate(newDate.getDate() + 1)
    currentDate.value = newDate
}

const updateOptions = options => {
    page.value = options.page
}

// Data loading methods
const getShiftLogs = async () => {
    try {
        const formattedDateParam = formatDateForAPI(currentDate.value)

        const response = await shiftLogService.getShiftLogs({
            page: page.value,
            limit: itemsPerPage.value !== -1 ? itemsPerPage.value : undefined,
            siteId: selectedSiteId.value,
            staffId: selectedStaffId.value,
            search: searchQuery.value,
            all: itemsPerPage.value === -1,
            start: formattedDateParam,
        })

        shiftLogs.value = response.data || []
        totalLogs.value = response.meta?.total || response.data?.length || 0
    } catch (error) {
        console.error("Error fetching shift logs:", error)
        snackbar.show("Failed to load shift logs", "error")
    }
}

const loadStaff = async () => {
    staffLoading.value = true
    try {
        const response = await shiftLogService.getStaff(staffSearch.value)

        staffList.value = response.staff || response.data || []
    } catch (error) {
        console.error("Error loading staff:", error)
    } finally {
        staffLoading.value = false
    }
}

// Event handlers
const handleRowClick = (event, { item }) => {
    if (!item) return

    const logItem = shiftLogs.value.find(log => log.id === item.id)
    if (logItem) {
        selectedShift.value = logItem
        if (shiftDetailsDialog.value) {
            shiftDetailsDialog.value.isDialogVisible = true
        }
    }
}

// Utility functions
const formatShiftDate = dateString => {
    if (!dateString) return "--"
    
    return dayjs(dateString).format("ddd, MMM D")
}

const formatTime = dateString => {
    if (!dateString) return "--"
    
    return dayjs(dateString).format("h:mm A")
}

const getTimeVariance = item => {
    if (!item.rostered_start || !item.claimed_start) return null

    const rosteredStart = dayjs(item.rostered_start)
    const claimedStart = dayjs(item.claimed_start)
    const startDiff = claimedStart.diff(rosteredStart, "minutes")

    // Check for late start
    if (startDiff > 5) {
        // More than 5 minutes late
        return {
            color: "error",
            icon: "tabler-alert-circle",
            message: `Checked in ${startDiff} minutes late`,
        }
    }

    // Check for early checkout if applicable
    if (item.rostered_end && item.claimed_end) {
        const rosteredEnd = dayjs(item.rostered_end)
        const claimedEnd = dayjs(item.claimed_end)
        const endDiff = rosteredEnd.diff(claimedEnd, "minutes")

        if (endDiff > 5) {
            // More than 5 minutes early
            return {
                color: "warning",
                icon: "tabler-clock-exclamation",
                message: `Checked out ${endDiff} minutes early`,
            }
        }
    }

    return null
}

// Watchers
watch(
    searchQuery,
    debounce(async () => {
        await getShiftLogs()
    }, 300),
)

watch([selectedSiteId, selectedStaffId], async () => {
    await getShiftLogs()
})

watch(currentDate, async () => {
    page.value = 1
    await getShiftLogs()
})

watch(
    staffSearch,
    debounce(async () => {
        await loadStaff()
    }, 300),
)

// Lifecycle
onMounted(async () => {
    // Load initial data
    await getShiftLogs()

    // Load staff list
    await loadStaff()
})
</script>

<style lang="scss" scoped>
.date-title {
    font-size: 1rem;
    font-weight: 500;
    min-width: 200px;
}

.cursor-pointer {
    cursor: pointer;
}
</style>
