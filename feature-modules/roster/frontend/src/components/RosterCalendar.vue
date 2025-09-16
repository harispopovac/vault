<template>
    <!-- Desktop Header -->
    <VCard class="header-line d-none d-sm-flex mb-4">
        <VCardTitle class="d-flex justify-space-between py-4 gap-4 w-full">
            <div class="d-flex align-center gap-4">
                <VBtn :disabled="loading" color="primary" @click="weekDown">
                    Prev
                </VBtn>
                <VBtn :disabled="loading" color="primary" @click="weekUp">
                    Next
                </VBtn>

                <!-- 👉 Site -->
                <AppSelect
                    v-if="sites.length > 0"
                    v-model="selectedSiteId"
                    :items="sites"
                    item-title="name"
                    item-value="id"
                    placeholder="Select Site"
                    style="min-width: 200px"
                />
                <VBtn :disabled="loading" color="primary" @click="toToday">
                    Today
                </VBtn>
            </div>

            <div class="d-flex gap-4">
                <AppTextField
                    v-model="searchQuery"
                    placeholder="Search"
                    style="min-width: 200px"
                />
                <CopyRosterDialog
                    v-model:is-dialog-visible="isCopyRosterDialogVisible"
                    :selected-site-id="selectedSiteId"
                    :week-start="weekStart"
                    :week-end="weekEnd"
                    :weeks="availableWeeks"
                    activator-type="button"
                    activator-text="Copy Week"
                    @get-roster="getStaffRoster"
                />
                <VBtn
                    @click="
                        selectedRoster = null;
                        isStoreUpdateRosterDialogVisible = true;
                    "
                >
                    + Roster
                </VBtn>
            </div>
        </VCardTitle>
    </VCard>

    <!-- Mobile Header -->
    <VExpansionPanels class="d-flex d-sm-none mb-4">
        <VExpansionPanel>
            <VExpansionPanelTitle>Filters & Actions</VExpansionPanelTitle>
            <VExpansionPanelText>
                <VRow class="py-2">
                    <VCol cols="12" sm="6">
                        <!-- 👉 Site -->
                        <AppSelect
                            v-if="sites.length > 0"
                            v-model="selectedSiteId"
                            :items="sites"
                            item-title="name"
                            item-value="id"
                            placeholder="Select Site"
                            density="compact"
                        />
                    </VCol>
                    <VCol cols="12" sm="6">
                        <AppTextField
                            v-model="searchQuery"
                            placeholder="Search"
                            density="compact"
                        />
                    </VCol>
                    <VCol cols="12">
                        <div class="d-flex gap-2 flex-wrap">
                            <VBtn
                                :disabled="loading"
                                color="primary"
                                size="small"
                                @click="toToday"
                            >
                                Today
                            </VBtn>
                            <CopyRosterDialog
                                v-model:is-dialog-visible="
                                    isCopyRosterDialogVisible
                                "
                                :selected-site-id="selectedSiteId"
                                :week-start="weekStart"
                                :week-end="weekEnd"
                                :weeks="availableWeeks"
                                activator-type="button"
                                activator-text="Copy Week"
                                @get-roster="getStaffRoster"
                            />
                            <VBtn
                                color="success"
                                size="small"
                                @click="
                                    selectedRoster = null;
                                    isStoreUpdateRosterDialogVisible = true;
                                "
                            >
                                + Roster
                            </VBtn>
                        </div>
                    </VCol>
                </VRow>
            </VExpansionPanelText>
        </VExpansionPanel>
    </VExpansionPanels>

    <!-- Main Calendar -->
    <VCard class="h-100 week">
        <div v-if="weekStart" class="week">
            <div
                v-for="(day, index) in weekDays"
                :key="index"
                class="day"
                :class="{ 'border-e-sm': index !== 6 }"
            >
                <div
                    class="header border-b-sm py-2 px-4"
                    :class="{
                        today: isToday(index),
                    }"
                >
                    <div class="d-flex justify-space-between align-center w-100"
                    >
                        <div class="d-flex flex-column">
                            <span class="title text-h5">
                                {{ day }}
                            </span>
                            <span class="text-body-1">
                                {{ getDateWithMonthName(index) }}
                            </span>
                        </div>
                        <VMenu>
                            <template #activator="{ props }">
                                <VBtn
                                    v-bind="props"
                                    icon="tabler-dots-vertical"
                                    variant="text"
                                    size="small"
                                    class="day-menu-btn"
                                />
                            </template>
                            <VList>
                                <VListItem
                                    prepend-icon="tabler-copy"
                                    @click="openCopyDayDialog(index, day)"
                                >
                                    <VListItemTitle>Copy Day</VListItemTitle>
                                </VListItem>
                                <VListItem
                                    prepend-icon="tabler-trash"
                                    color="error"
                                    @click="setSelectedDayForClear(index)"
                                >
                                    <VListItemTitle>Clear Day</VListItemTitle>
                                </VListItem>
                            </VList>
                        </VMenu>
                    </div>
                </div>

                <div class="content">
                    <div
                        v-if="
                            filteredRoster[index] &&
                                filteredRoster[index][1].length
                        "
                    >
                        <div
                            v-for="(item, i) in siteRosterFilter(
                                filteredRoster[index][1],
                            )"
                            :key="i"
                            class="item border-b-sm pa-2 px-4"
                            @click="
                                selectedRoster = item;
                                isStoreUpdateRosterDialogVisible = true;
                            "
                        >
                            <span
                                class="text-h6 text-no-wrap"
                                :class="{
                                    absent: !!item.absent_notes,
                                }"
                            >
                                {{ item.fname }} {{ item.sname }}
                            </span>

                            <span class="text-sm text-no-wrap">
                                {{ formatTime(item.rostered_start) }}
                                &nbsp;&nbsp;-&nbsp;&nbsp;
                                {{ formatTime(item.rostered_end) }}
                                {{
                                    getDayDifference(
                                        item.rostered_start,
                                        item.rostered_end,
                                    )
                                        ? "+" +
                                            getDayDifference(
                                                item.rostered_start,
                                                item.rostered_end,
                                            )
                                        : ""
                                }}
                            </span>
                        </div>
                    </div>
                    <div class="buttons-mobile">
                        <VBtn
                            color="success"
                            size="small"
                            @click="
                                selectedRoster = null;
                                isStoreUpdateRosterDialogVisible = true;
                            "
                        >
                            + Roster
                        </VBtn>
                    </div>
                </div>
            </div>
        </div>
        <div
            v-else
            class="d-flex justify-center align-center"
            style="height: 200px"
        >
            <VProgressCircular indeterminate />
        </div>
    </VCard>

    <!-- Dialogs -->
    <StoreUpdateRosterDialog
        :is-dialog-visible="isStoreUpdateRosterDialogVisible"
        :selected-site-id="selectedSiteId"
        :data="selectedRoster"
        :sites="sites"
        @update:is-dialog-visible="isStoreUpdateRosterDialogVisible = false"
        @get-roster="getStaffRoster"
    />

    <CopyDayDialog
        ref="copyDayDialogRef"
        v-model:is-dialog-visible="isCopyDayDialogVisible"
        :source-day-index="selectedDayForCopy?.index"
        :source-day-name="selectedDayForCopy?.name"
        :selected-site-id="selectedSiteId"
        :week-start="weekStart"
        :week-end="weekEnd"
        @get-roster="getStaffRoster"
    />
</template>

<script setup>
import { useSnackbarStore } from "@/stores/snackbar"
import dayjs from "dayjs"
import _ from "lodash"
import { fetchRosters } from "../services/staffRosterService"
import CopyDayDialog from "./dialogs/CopyDayDialog.vue"
import CopyRosterDialog from "./dialogs/CopyRosterDialog.vue"
import StoreUpdateRosterDialog from "./dialogs/StoreUpdateRosterDialog.vue"

const props = defineProps({
    siteId: {
        type: Number,
        default: null,
    },
    sites: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(["roster-loaded", "error"])

const snackbar = useSnackbarStore()

const weekDays = [
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
    "Sunday",
]

const months = [
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

const currentDate = ref(new Date())
const weekStart = ref(null)
const weekEnd = ref(null)
const roster = ref([])
const loading = ref(false)
const selectedSiteId = ref(props.siteId || null)
const selectedRoster = ref(null)
const isStoreUpdateRosterDialogVisible = ref(false)
const isCopyRosterDialogVisible = ref(false)
const isCopyDayDialogVisible = ref(false)
const selectedDayForCopy = ref(null)
const copyDayDialogRef = ref(null)
const searchQuery = ref("")
const availableWeeks = ref([])

watch(
    () => props.siteId,
    newVal => {
        if (newVal) {
            selectedSiteId.value = newVal
            if (weekStart.value) {
                getStaffRoster()
            }
        }
    },
)

watch(
    () => props.sites,
    newVal => {
        if (newVal.length > 0 && !selectedSiteId.value) {
            selectedSiteId.value = newVal[0].id
            if (weekStart.value) {
                getStaffRoster()
            }
        }
    },
)

watch(selectedSiteId, (newVal, oldVal) => {
    if (oldVal !== undefined && newVal !== oldVal && weekStart.value) {
        getStaffRoster()
    }
})

onMounted(() => {
    currentDate.value = new Date()
    calculateWeekBounds(currentDate.value)

    // Only generate available weeks after weekStart is calculated
    if (weekStart.value) {
        generateAvailableWeeks()
    }

    if (props.sites.length > 0 && !selectedSiteId.value) {
        selectedSiteId.value = props.sites[0].id
    }

    // Only call getStaffRoster after weekStart is calculated
    if (weekStart.value) {
        getStaffRoster()
    }
})

const calculateWeekBounds = date => {
    const currentWeekDay = date.getDay()
    const lessDays = currentWeekDay === 0 ? 6 : currentWeekDay - 1

    weekStart.value = new Date(
        new Date(date).setDate(date.getDate() - lessDays),
    )
    weekEnd.value = new Date(
        new Date(weekStart.value).setDate(weekStart.value.getDate() + 6),
    )
}

const getStaffRoster = async () => {
    if (!weekStart.value || !weekEnd.value) return

    loading.value = true

    try {
        const params = {
            site_id: selectedSiteId.value || null,
            start: formatDate(weekStart.value),
            end: formatDate(weekEnd.value),
        }

        const response = await fetchRosters(params)

        groupRosterByDays(response || [])
        emit("roster-loaded", response || [])
    } catch (error) {
        console.error("Failed to load roster:", error)
        emit("error", error)
        snackbar.show("Failed to load roster", "error")
    } finally {
        loading.value = false
    }
}

const groupRosterByDays = data => {
    if (!weekStart.value) return

    const dataWithDates = data.map(item => ({
        ...item,
        date: item.rostered_start_date,
        fname: item.fname || "Unknown",
        sname: item.sname || "Staff",
    }))

    const groupedByDate = _.groupBy(dataWithDates, "date")
    const daysOfWeek = []

    for (let i = 0; i < 7; i++) {
        const dayDate = new Date(weekStart.value.getTime() + i * 86400000)
        const dateString = formatDate(dayDate)

        daysOfWeek.push(dateString)
    }

    roster.value = daysOfWeek.map(day => [day, groupedByDate[day] || []])
}

const formatDate = date => {
    const d = new Date(date)

    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`
}

const formatTime = datetime => {
    return dayjs(datetime).format("h:mma")
}

const getDateWithMonthName = date => {
    if (typeof date === "number") {
        // Handle dayIndex case
        if (!weekStart.value) return ""

        const d = new Date(
            weekStart.value.getTime() + date * 24 * 60 * 60 * 1000,
        )

        return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`
    }

    // Handle date object case
    const d = new Date(date)

    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`
}

const isToday = dayIndex => {
    if (!weekStart.value) return false

    const today = new Date()

    const dayDate = new Date(
        weekStart.value.getTime() + dayIndex * 24 * 60 * 60 * 1000,
    )

    return formatDate(today) === formatDate(dayDate)
}

const weekDown = () => {
    currentDate.value = new Date(
        currentDate.value.getTime() - 7 * 24 * 60 * 60 * 1000,
    )
    calculateWeekBounds(currentDate.value)
    generateAvailableWeeks()
    getStaffRoster()
}

const weekUp = () => {
    currentDate.value = new Date(
        currentDate.value.getTime() + 7 * 24 * 60 * 60 * 1000,
    )
    calculateWeekBounds(currentDate.value)
    generateAvailableWeeks()
    getStaffRoster()
}

const toToday = () => {
    currentDate.value = new Date()
    calculateWeekBounds(currentDate.value)
    generateAvailableWeeks()
    getStaffRoster()
}

const getDayDifference = (start, end) => {
    return dayjs(end).diff(dayjs(start), "day")
}

const siteRosterFilter = data => {
    return data
        .filter(
            item =>
                item.site_id === selectedSiteId.value || !selectedSiteId.value,
        )
        .sort((a, b) => {
            // First sort by start time
            const timeA = formatTime(a.rostered_start)
            const timeB = formatTime(b.rostered_start)
            if (timeA !== timeB) {
                return timeA.localeCompare(timeB)
            }

            // If times are equal, sort by name
            const nameA = `${a.fname} ${a.sname}`.toLowerCase()
            const nameB = `${b.fname} ${b.sname}`.toLowerCase()

            return nameA.localeCompare(nameB)
        })
}

const filteredRoster = computed(() => {
    if (!roster.value || !Array.isArray(roster.value)) return []

    if (searchQuery.value.trim() === "") {
        return roster.value
    } else {
        const q = searchQuery.value.trim().toLowerCase()

        return roster.value.map(item => {
            const filteredChildElement = item[1].filter(childItem => {
                const fullName =
                    `${childItem.fname} ${childItem.sname}`.toLowerCase()

                return fullName.includes(q)
            })

            return [item[0], filteredChildElement]
        })
    }
})

const openCopyDayDialog = (dayIndex, dayName) => {
    selectedDayForCopy.value = { index: dayIndex, name: dayName }
    copyDayDialogRef.value.open(dayIndex, dayName)
}

const generateAvailableWeeks = () => {
    if (!weekStart.value) return

    const weeks = []

    // Use weekStart instead of currentDate to ensure proper week boundaries
    const startDate = new Date(weekStart.value)

    // Generate next 8 weeks
    for (let i = 1; i <= 8; i++) {
        const weekStartDate = new Date(startDate)

        weekStartDate.setDate(startDate.getDate() + i * 7)

        const weekEndDate = new Date(weekStartDate)

        weekEndDate.setDate(weekStartDate.getDate() + 6)

        weeks.push({
            title: `${getDateWithMonthName(weekStartDate)} - ${getDateWithMonthName(weekEndDate)}`,
            start: formatDate(weekStartDate),
            end: formatDate(weekEndDate),
            n: i,
        })
    }

    availableWeeks.value = weeks
}

const setSelectedDayForClear = dayIndex => {
    // TODO: Implement clear day functionality
    console.log("Clear day:", dayIndex)
}
</script>

<style lang="scss" scoped>
.absent {
    color: #aa4444 !important;
}

.v-theme--light {
    .today {
        background-color: #e3effa !important;
    }
    .header-line {
        background: #f6f6f6;
    }
}

.v-theme--dark {
    .today {
        background-color: rgba(227, 239, 250, 0.22) !important;
    }
}

.week {
    display: flex;
    width: 100%;
    height: 100%;
    overflow-x: auto;
}

.day {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: center;
    flex: 1 1 100%;
}

.header {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 100%;
    position: relative;
}

.content {
    display: flex;
    flex-direction: column;
    width: 100%;
    height: calc(100vh - 315px);
    overflow-y: auto;

    .buttons-mobile {
        display: none;
    }

    &::-webkit-scrollbar {
        width: 2px;
    }

    &::-webkit-scrollbar-thumb {
        background: rgba(var(--v-theme-secondary), 0.3);
    }
}

.item {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: start;

    &:hover {
        cursor: pointer;
        background-color: #f0f0f0;
    }
}

.day-menu-btn {
    opacity: 0.7;
    transition: opacity 0.2s ease;
}

.day-menu-btn:hover {
    opacity: 1;
}

@media screen and (max-width: 600px) {
    .week {
        overflow-x: scroll;
        scroll-snap-type: x mandatory;
        scrollbar-width: none;
    }

    .day {
        flex: 0 0 100%;
        scroll-snap-align: start;
    }

    .item {
        align-items: flex-start;
    }

    .content {
        padding-bottom: 64px;

        .buttons-mobile {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            overflow: hidden;
            position: absolute;
            bottom: 16px;
            width: 100%;

            button {
                flex: 1;
            }
        }
    }
}
</style>
