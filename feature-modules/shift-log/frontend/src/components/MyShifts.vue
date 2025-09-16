<template>
    <section>
        <VCard>
            <!-- Header Controls -->
            <VCardTitle
                v-if="$vuetify.display.lgAndUp"
                class="d-flex justify-space-between py-4 gap-4 w-full"
            >
                <!-- Show Past Shifts Toggle -->
                <div class="me-3 d-flex gap-3">
                    <div class="d-flex align-center">
                        <VSwitch
                            v-model="showPastShifts"
                            label="Show Shift History"
                            color="primary"
                            hide-details
                        />
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex gap-4">
                    <StoreUpdateShiftLogDialog
                        v-if="!hasCheckInButtonsInRows"
                        activator-type="button"
                        my-shifts
                        :data="{}"
                        :selected-site-id="null"
                        :open-shift="openShift"
                        @refresh="getMyShifts"
                    />
                </div>
            </VCardTitle>

            <!-- Mobile/Tablet Layout -->
            <VCardTitle v-else class="py-4 w-full">
                <div class="d-flex flex-column gap-4">
                    <!-- Top Row - Controls -->
                    <div class="d-flex justify-space-between align-center gap-4"
                    >
                        <div class="d-flex gap-2 align-center">
                            <AppSelect
                                :model-value="itemsPerPage"
                                :items="[
                                    { value: 5, title: '5' },
                                    { value: 100, title: '100' },
                                    { value: 250, title: '250' },
                                    { value: 500, title: '500' },
                                    { value: 1000, title: '1000' },
                                    { value: -1, title: 'All' },
                                ]"
                                style="inline-size: 5rem"
                                density="compact"
                                @update:model-value="
                                    itemsPerPage = parseInt($event, 10)
                                "
                            />
                            <VSwitch
                                v-if="$vuetify.display.mdAndUp"
                                v-model="showPastShifts"
                                label="Show Past Shifts"
                                color="primary"
                                hide-details
                                density="compact"
                            />
                        </div>

                        <StoreUpdateShiftLogDialog
                            v-if="!hasCheckInButtonsInRows"
                            activator-type="button"
                            my-shifts
                            :data="{}"
                            :selected-site-id="null"
                            :open-shift="openShift"
                            @refresh="getMyShifts"
                        />
                    </div>

                    <!-- Search Row -->
                    <AppTextField
                        v-model="searchQuery"
                        placeholder="Search"
                        prepend-inner-icon="tabler-search"
                    />

                    <!-- Show Past Switch (mobile only) -->
                    <div class="d-flex d-sm-none align-center">
                        <VSwitch
                            v-model="showPastShifts"
                            label="Show Past Shifts"
                            color="primary"
                            hide-details
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
                item-value="staff_roster_id"
                @update:options="updateOptions"
                @click:row="handleRowClick"
            >
                <!-- Shift Date Column -->
                <template #[`item.shift-date`]="{ item }">
                    <div class="d-flex align-center">
                        <div class="d-flex flex-column">
                            <span
                                v-if="item.rostered_start || item.rostered_end"
                                class="text-body-1"
                            >
                                {{ formatShiftDate(item.rostered_start) }}
                            </span>
                            <span
                                v-else-if="item.claimed_start"
                                class="text-body-1"
                            >
                                {{ formatShiftDate(item.claimed_start) }}
                            </span>
                            <span v-else class="text-body-1">--</span>
                        </div>

                        <!-- Overtime Indicator -->
                        <span
                            v-if="
                                shiftLogConfig.enable_overtime && item.ot_claim
                            "
                            class="ml-2"
                        >
                            <VTooltip location="top">
                                <template #activator="{ props }">
                                    <VIcon
                                        v-bind="props"
                                        color="warning"
                                        icon="tabler-clock-exclamation"
                                        class="cursor-pointer"
                                        size="16"
                                    />
                                </template>
                                <span>Overtime Requested</span>
                            </VTooltip>
                        </span>
                    </div>
                </template>

                <!-- Rostered Time Column -->
                <template #[`item.rostered-time`]="{ item }">
                    <div class="d-flex align-center">
                        <div class="d-flex flex-column">
                            <span
                                v-if="item.rostered_start || item.rostered_end"
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
                        <div class="d-flex align-items-center w-100">
                            <div class="d-flex flex-column w-100">
                                <!-- Show when both start and end times exist -->
                                <span
                                    v-if="
                                        item.claimed_start && item.claimed_end
                                    "
                                    class="text-body-1 d-flex justify-content-between w-100"
                                >
                                    <span>
                                        {{ formatTime(item.claimed_start) }} —
                                        {{ formatTime(item.claimed_end) }}
                                    </span>

                                    <!-- Late/Early indicators -->
                                    <span
                                        v-if="getTimeVariance(item)"
                                        class="ml-auto"
                                    >
                                        <VTooltip location="top">
                                            <template #activator="{ props }">
                                                <VIcon
                                                    v-bind="props"
                                                    :color="
                                                        getTimeVariance(item)
                                                            .color
                                                    "
                                                    :icon="
                                                        getTimeVariance(item)
                                                            .icon
                                                    "
                                                    class="cursor-pointer"
                                                    size="16"
                                                />
                                            </template>
                                            <span>{{
                                                getTimeVariance(item).message
                                            }}</span>
                                        </VTooltip>
                                    </span>
                                </span>

                                <!-- Show when only start time exists (shift in progress) -->
                                <span
                                    v-else-if="
                                        item.claimed_start && !item.claimed_end
                                    "
                                    class="text-body-1 d-flex justify-content-between w-100"
                                >
                                    <span>
                                        {{ formatTime(item.claimed_start) }} —
                                        <span class="text-warning font-weight-bold"
                                        >In Progress</span
                                        >
                                    </span>
                                    <span
                                        v-if="getTimeVariance(item)"
                                        class="ml-auto"
                                    >
                                        <VTooltip location="top">
                                            <template #activator="{ props }">
                                                <VIcon
                                                    v-bind="props"
                                                    :color="
                                                        getTimeVariance(item)
                                                            .color
                                                    "
                                                    :icon="
                                                        getTimeVariance(item)
                                                            .icon
                                                    "
                                                    class="cursor-pointer"
                                                    size="16"
                                                />
                                            </template>
                                            <span>{{
                                                getTimeVariance(item).message
                                            }}</span>
                                        </VTooltip>
                                    </span>
                                </span>

                                <!-- Show when no claimed times exist -->
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
                    <div class="d-flex align-center gap-2">
                        <div
                            v-if="shouldShowCheckInButton(item)"
                            class="d-flex flex-column"
                        >
                            <StoreUpdateShiftLogDialog
                                v-if="!item.actual_start"
                                ref="storeUpdateShiftLogDialogRef"
                                activator-type="check-in"
                                my-shifts
                                :data="item"
                                :selected-site-id="null"
                                :open-shift="openShift"
                                :config="shiftLogConfig"
                                @refresh="getMyShifts"
                            />

                            <StoreUpdateShiftLogDialog
                                v-else-if="!item.actual_end"
                                ref="storeUpdateShiftLogDialogRef"
                                activator-type="check-out"
                                my-shifts
                                :data="item"
                                :selected-site-id="null"
                                :open-shift="openShift"
                                :config="shiftLogConfig"
                                @refresh="getMyShifts"
                            />
                        </div>
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
        <MyShiftDetailsDialog
            v-model:is-dialog-visible="dialogVisible"
            :data="selectedShift"
        />
    </section>
</template>

<script setup>
import { useSnackbarStore } from "@/stores/snackbar"
import dayjs from "dayjs"
import { debounce } from "lodash"
import { computed, onMounted, ref, watch } from "vue"
import shiftLogService from "../services/shiftLogService.js"
import MyShiftDetailsDialog from "./dialogs/MyShiftDetailsDialog.vue"
import StoreUpdateShiftLogDialog from "./dialogs/StoreUpdateShiftLogDialog.vue"

const snackbar = useSnackbarStore()

// Reactive state
const shiftLogs = ref([])
const page = ref(1)
const itemsPerPage = ref(100)
const searchQuery = ref("")
const totalLogs = ref(0)
const showPastShifts = ref(false)
const openShift = ref(null)

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
    },
})

// Dialog state
const dialogVisible = ref(false)
const selectedShift = ref({})

// Dialog refs
const storeUpdateShiftLogDialogRef = ref(null)

// Table headers
const headers = computed(() => {
    const baseHeaders = [
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

// Function to check if check-in button should show for a specific item
const shouldShowCheckInButton = item => {
    // If already checked in and out, no buttons should show
    if (item.actual_start && item.actual_end) {
        return false
    }

    // Calculate 8 days ago and today
    const now = new Date()
    const eightDaysAgo = new Date(now.getTime() - 8 * 24 * 60 * 60 * 1000)

    const endOfToday = new Date(
        now.getFullYear(),
        now.getMonth(),
        now.getDate(),
        23,
        59,
        59,
    )

    // If user hasn't checked in yet, apply time restrictions for check-in
    if (!item.actual_start) {
        // For unrostered shifts (no rostered_start), check if not more than 8 days old
        if (!item.rostered_start) {
            // If there's a claimed_start, use that to check if it's more than 8 days old
            if (item.claimed_start) {
                const claimedDate = new Date(item.claimed_start)
                
                return claimedDate >= eightDaysAgo && claimedDate <= endOfToday
            }

            // If no claimed_start, keep original behavior (always show)
            return true
        }

        // Compare dates: not more than 8 days ago AND not after today
        const rosteredDate = new Date(item.rostered_start)
        
        return rosteredDate >= eightDaysAgo && rosteredDate <= endOfToday
    }

    // If user has checked in but not checked out, allow check-out regardless of time restriction
    if (item.actual_start && !item.actual_end) {
        return true
    }

    return false
}

// Computed property to check if any rows have check-in or check-out buttons
const hasCheckInButtonsInRows = computed(() => {
    return shiftLogs.value.some(item => {
        // Skip if already completely finished (checked in and out)
        if (item.actual_start && item.actual_end) {
            return false
        }

        // Check if this item should show any action buttons (check-in or check-out)
        return shouldShowCheckInButton(item)
    })
})

// Methods
const updateOptions = options => {
    page.value = options.page
}

const getMyShifts = async () => {
    try {
        const response = await shiftLogService.getMyShifts({
            page: page.value,
            limit: itemsPerPage.value !== -1 ? itemsPerPage.value : undefined,
            search: searchQuery.value,
            showPastShifts: showPastShifts.value,
            all: itemsPerPage.value === -1,
        })

        shiftLogs.value = response.data || []
        totalLogs.value = response.meta?.total || response.data?.length || 0

        // Extract open shift information
        openShift.value = response.open_shift || null
    } catch (error) {
        console.error("Error fetching my shifts:", error)
        snackbar.show("Failed to load shifts", "error")
    }
}

const handleRowClick = (event, { item }) => {
    if (!item) return

    // Find the actual item data from shiftLogs
    const logItem = shiftLogs.value.find(log => log.id === item.id)
    if (logItem) {
        selectedShift.value = logItem
        dialogVisible.value = true
    }
}

// Utility functions
const formatShiftDate = dateString => {
    if (!dateString) return "--"

    const date = new Date(dateString)

    // Format: Sun, 22nd Jun 2025
    const dayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"]

    const monthNames = [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
    ]

    const dayName = dayNames[date.getDay()]
    const day = date.getDate()
    const monthName = monthNames[date.getMonth()]
    const year = date.getFullYear()

    // Add ordinal suffix (st, nd, rd, th)
    const getOrdinalSuffix = day => {
        if (day > 3 && day < 21) return "th"
        switch (day % 10) {
        case 1:
            return "st"
        case 2:
            return "nd"
        case 3:
            return "rd"
        default:
            return "th"
        }
    }

    return `${dayName}, ${day}${getOrdinalSuffix(day)} ${monthName} ${year}`
}

const formatTime = dateString => {
    if (!dateString) return "--"
    
    return dayjs(dateString).format("h:mm A")
}

const getTimeVariance = item => {
    const messages = []

    // Check for late start
    if (item.rostered_start && item.claimed_start) {
        const rosteredStart = dayjs(item.rostered_start)
        const claimedStart = dayjs(item.claimed_start)
        const startDiff = claimedStart.diff(rosteredStart, "minutes")

        if (startDiff > 5) {
            return {
                color: "error",
                icon: "tabler-alert-circle",
                message: `Checked in ${startDiff} minutes late`,
            }
        }
    }

    // Check for early checkout
    if (item.rostered_end && item.claimed_end) {
        const rosteredEnd = dayjs(item.rostered_end)
        const claimedEnd = dayjs(item.claimed_end)
        const endDiff = rosteredEnd.diff(claimedEnd, "minutes")

        if (endDiff > 5) {
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
        await getMyShifts()
    }, 300),
)

watch(showPastShifts, async () => {
    page.value = 1
    await getMyShifts()
})

// Lifecycle
onMounted(async () => {
    await getMyShifts()
})
</script>

<style lang="scss" scoped>
.cursor-pointer {
    cursor: pointer;
}
</style>
