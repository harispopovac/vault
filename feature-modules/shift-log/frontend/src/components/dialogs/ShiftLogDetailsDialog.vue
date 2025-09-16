<template>
    <VDialog v-model="isDialogVisible" max-width="800px" persistent>
        <VCard>
            <VCardTitle class="d-flex justify-space-between align-center">
                <span>Shift Log Details</span>
                <VBtn icon variant="text" @click="closeDialog">
                    <VIcon icon="tabler-x" />
                </VBtn>
            </VCardTitle>

            <VDivider />

            <VCardText>
                <div v-if="data && Object.keys(data).length > 0">
                    <div class="d-flex flex-column gap-6">
                        <!-- Staff Info -->
                        <VCard variant="outlined" class="pa-4">
                            <VCardTitle class="text-h6 mb-2"
                            >Staff Information</VCardTitle
                            >
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex justify-space-between">
                                    <span class="font-weight-medium"
                                    >Name:</span
                                    >
                                    <span
                                    >{{ data.fname }} {{ data.sname }}</span
                                    >
                                </div>
                                <div
                                    v-if="config.enable_sites"
                                    class="d-flex justify-space-between"
                                >
                                    <span class="font-weight-medium"
                                    >{{
                                        config.labels?.grouping_label ||
                                            "Site"
                                    }}:</span
                                    >
                                    <span>{{
                                        data.site_name || "Not assigned"
                                    }}</span>
                                </div>
                            </div>
                        </VCard>

                        <!-- Shift Times -->
                        <VCard variant="outlined" class="pa-4">
                            <VCardTitle class="text-h6 mb-2"
                            >Shift Times</VCardTitle
                            >
                            <div class="d-flex flex-column gap-2">
                                <!-- Rostered Times (if available) -->
                                <template
                                    v-if="
                                        data.rostered_start || data.rostered_end
                                    "
                                >
                                    <div class="d-flex justify-space-between">
                                        <span class="font-weight-medium"
                                        >Rostered Start:</span
                                        >
                                        <span>{{
                                            formatDateTime(data.rostered_start)
                                        }}</span>
                                    </div>
                                    <div class="d-flex justify-space-between">
                                        <span class="font-weight-medium"
                                        >Rostered End:</span
                                        >
                                        <span>{{
                                            formatDateTime(data.rostered_end)
                                        }}</span>
                                    </div>
                                    <VDivider class="my-2" />
                                </template>

                                <!-- Claimed Times -->
                                <div class="d-flex justify-space-between">
                                    <span class="font-weight-medium"
                                    >Claimed Start:</span
                                    >
                                    <span
                                        :class="
                                            getTimeVarianceClass(
                                                data.rostered_start,
                                                data.claimed_start,
                                            )
                                        "
                                    >
                                        {{ formatDateTime(data.claimed_start) }}
                                    </span>
                                </div>
                                <div class="d-flex justify-space-between">
                                    <span class="font-weight-medium"
                                    >Claimed End:</span
                                    >
                                    <span
                                        :class="
                                            getTimeVarianceClass(
                                                data.rostered_end,
                                                data.claimed_end,
                                            )
                                        "
                                    >
                                        {{ formatDateTime(data.claimed_end) }}
                                    </span>
                                </div>

                                <VDivider class="my-2" />

                                <!-- Actual Times -->
                                <div class="d-flex justify-space-between">
                                    <span class="font-weight-medium"
                                    >Actual Start:</span
                                    >
                                    <span>{{
                                        formatDateTime(data.actual_start)
                                    }}</span>
                                </div>
                                <div class="d-flex justify-space-between">
                                    <span class="font-weight-medium"
                                    >Actual End:</span
                                    >
                                    <span>{{
                                        formatDateTime(data.actual_end)
                                    }}</span>
                                </div>

                                <!-- Time Variance Indicators -->
                                <div
                                    v-if="getTimeVarianceMessage()"
                                    class="mt-3"
                                >
                                    <VAlert
                                        :color="getTimeVarianceColor()"
                                        variant="tonal"
                                        density="compact"
                                    >
                                        {{ getTimeVarianceMessage() }}
                                    </VAlert>
                                </div>

                                <!-- Duration Summary -->
                                <div class="mt-3 pa-3 bg-surface-variant rounded"
                                >
                                    <div class="d-flex justify-space-between mb-1"
                                    >
                                        <span class="font-weight-medium"
                                        >Total Time:</span
                                        >
                                        <span>{{ calculateTotalTime() }}</span>
                                    </div>
                                    <div
                                        v-if="
                                            data.rostered_start &&
                                                data.rostered_end
                                        "
                                        class="d-flex justify-space-between"
                                    >
                                        <span class="font-weight-medium"
                                        >Rostered Time:</span
                                        >
                                        <span>{{
                                            calculateRosteredTime()
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </VCard>

                        <!-- Break Information (if enabled) -->
                        <VCard
                            v-if="
                                shiftLogConfig.enable_breaks &&
                                    data.breaks &&
                                    data.breaks.length > 0
                            "
                            variant="outlined"
                            class="pa-4"
                        >
                            <VCardTitle class="text-h6 mb-2">Breaks</VCardTitle>
                            <div class="d-flex flex-column gap-2">
                                <div
                                    v-for="(breakItem, index) in data.breaks"
                                    :key="index"
                                    class="d-flex justify-space-between align-center pa-2 rounded border"
                                >
                                    <div>
                                        <div class="text-body-1">
                                            {{
                                                formatTime(
                                                    breakItem.break_start,
                                                )
                                            }}
                                            -
                                            {{
                                                formatTime(breakItem.break_end)
                                            }}
                                        </div>
                                        <div
                                            v-if="breakItem.notes"
                                            class="text-caption text-medium-emphasis"
                                        >
                                            {{ breakItem.notes }}
                                        </div>
                                    </div>
                                    <VChip size="small" color="info">
                                        {{
                                            calculateBreakDuration(
                                                breakItem.break_start,
                                                breakItem.break_end,
                                            )
                                        }}
                                    </VChip>
                                </div>

                                <!-- Total break time -->
                                <div class="mt-2 pa-2 bg-surface-variant rounded"
                                >
                                    <div class="d-flex justify-space-between">
                                        <span class="font-weight-medium"
                                        >Total Break Time:</span
                                        >
                                        <span>{{
                                            calculateTotalBreakTime()
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </VCard>

                        <!-- Overtime Information (if enabled) -->
                        <VCard
                            v-if="
                                shiftLogConfig.enable_overtime && data.ot_claim
                            "
                            variant="outlined"
                            class="pa-4"
                        >
                            <VCardTitle class="text-h6 mb-2"
                            >Overtime Request</VCardTitle
                            >
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex justify-space-between">
                                    <span class="font-weight-medium"
                                    >Duration:</span
                                    >
                                    <span>{{
                                        formatOvertimeMinutes(data.ot_claim)
                                    }}</span>
                                </div>
                                <div
                                    v-if="data.ot_reason"
                                    class="d-flex justify-space-between"
                                >
                                    <span class="font-weight-medium"
                                    >Reason:</span
                                    >
                                    <span>{{ data.ot_reason }}</span>
                                </div>
                                <div class="d-flex justify-space-between">
                                    <span class="font-weight-medium"
                                    >Status:</span
                                    >
                                    <VChip
                                        :color="
                                            getOvertimeStatusColor(
                                                data.ot_response,
                                            )
                                        "
                                        size="small"
                                        variant="tonal"
                                    >
                                        {{
                                            getOvertimeStatusText(
                                                data.ot_response,
                                            )
                                        }}
                                    </VChip>
                                </div>
                                <div
                                    v-if="data.ot_responded_by"
                                    class="d-flex justify-space-between"
                                >
                                    <span class="font-weight-medium"
                                    >Responded By:</span
                                    >
                                    <span>{{
                                        data.ot_responded_by_name ||
                                            data.ot_responded_by
                                    }}</span>
                                </div>
                                <div
                                    v-if="data.ot_response_notes"
                                    class="d-flex justify-space-between"
                                >
                                    <span class="font-weight-medium"
                                    >Response Notes:</span
                                    >
                                    <span>{{ data.ot_response_notes }}</span>
                                </div>
                            </div>
                        </VCard>

                        <!-- Comments (if enabled) -->
                        <VCard
                            v-if="
                                shiftLogConfig.enable_comments &&
                                    (data.checkin_comments ||
                                        data.checkout_comments)
                            "
                            variant="outlined"
                            class="pa-4"
                        >
                            <VCardTitle class="text-h6 mb-2"
                            >Comments</VCardTitle
                            >
                            <div class="d-flex flex-column gap-3">
                                <div v-if="data.checkin_comments">
                                    <VLabel class="text-body-2 font-weight-medium mb-1"
                                    >Check-in Comments</VLabel
                                    >
                                    <div class="text-body-2">
                                        {{ data.checkin_comments }}
                                    </div>
                                </div>
                                <div v-if="data.checkout_comments">
                                    <VLabel class="text-body-2 font-weight-medium mb-1"
                                    >Check-out Comments</VLabel
                                    >
                                    <div class="text-body-2">
                                        {{ data.checkout_comments }}
                                    </div>
                                </div>
                            </div>
                        </VCard>

                        <!-- Photos (if enabled) -->
                        <VCard
                            v-if="
                                shiftLogConfig.enable_photos &&
                                    (data.check_in_photo || data.check_out_photo)
                            "
                            variant="outlined"
                            class="pa-4"
                        >
                            <VCardTitle class="text-h6 mb-2">Photos</VCardTitle>
                            <div class="d-flex flex-wrap gap-4">
                                <div
                                    v-if="data.check_in_photo"
                                    class="d-flex flex-column align-center"
                                >
                                    <VLabel class="text-body-2 mb-2"
                                    >Check-in Photo</VLabel
                                    >
                                    <VImg
                                        :src="data.check_in_photo"
                                        width="200"
                                        height="150"
                                        class="rounded cursor-pointer"
                                        style="transform: scaleX(-1)"
                                        @click="
                                            openPhotoDialog(
                                                data.check_in_photo,
                                                'Check-in Photo',
                                            )
                                        "
                                    />
                                </div>
                                <div
                                    v-if="data.check_out_photo"
                                    class="d-flex flex-column align-center"
                                >
                                    <VLabel class="text-body-2 mb-2"
                                    >Check-out Photo</VLabel
                                    >
                                    <VImg
                                        :src="data.check_out_photo"
                                        width="200"
                                        height="150"
                                        class="rounded cursor-pointer"
                                        style="transform: scaleX(-1)"
                                        @click="
                                            openPhotoDialog(
                                                data.check_out_photo,
                                                'Check-out Photo',
                                            )
                                        "
                                    />
                                </div>
                            </div>
                        </VCard>

                        <!-- IP Tracking (if enabled) -->
                        <VCard
                            v-if="
                                shiftLogConfig.enable_ip_tracking &&
                                    (data.checkin_ip || data.checkout_ip)
                            "
                            variant="outlined"
                            class="pa-4"
                        >
                            <VCardTitle class="text-h6 mb-2"
                            >IP Tracking</VCardTitle
                            >
                            <div class="d-flex flex-column gap-2">
                                <div
                                    v-if="data.checkin_ip"
                                    class="d-flex justify-space-between"
                                >
                                    <span class="font-weight-medium"
                                    >Check-in IP:</span
                                    >
                                    <span class="font-family-monospace">{{
                                        data.checkin_ip
                                    }}</span>
                                </div>
                                <div
                                    v-if="data.checkout_ip"
                                    class="d-flex justify-space-between"
                                >
                                    <span class="font-weight-medium"
                                    >Check-out IP:</span
                                    >
                                    <span class="font-family-monospace">{{
                                        data.checkout_ip
                                    }}</span>
                                </div>
                                <div
                                    v-if="data.site_ip"
                                    class="d-flex justify-space-between"
                                >
                                    <span class="font-weight-medium"
                                    >Site IP:</span
                                    >
                                    <span class="font-family-monospace">{{
                                        data.site_ip
                                    }}</span>
                                </div>
                            </div>
                        </VCard>
                    </div>
                </div>
            </VCardText>

            <VDivider />

            <VCardActions class="justify-space-between">
                <div class="d-flex gap-2">
                    <VBtn
                        v-if="can(['manage_shifts']) && data.id"
                        color="error"
                        variant="outlined"
                        @click="confirmDelete"
                    >
                        Delete
                    </VBtn>
                </div>

                <div class="d-flex gap-2">
                    <VBtn
                        v-if="can(['manage_shifts']) && data.id"
                        color="primary"
                        variant="outlined"
                        @click="openEditDialog"
                    >
                        Edit
                    </VBtn>
                    <VBtn
                        color="secondary"
                        variant="tonal"
                        @click="closeDialog"
                    >
                        Close
                    </VBtn>
                </div>
            </VCardActions>
        </VCard>

        <!-- Confirmation Dialog -->
        <VDialog v-model="confirmDeleteDialog" max-width="500px">
            <VCard>
                <VCardTitle>Confirm Deletion</VCardTitle>
                <VCardText>
                    Are you sure you want to delete this shift log? This action
                    cannot be undone.
                </VCardText>
                <VCardActions class="justify-end">
                    <VBtn
                        color="secondary"
                        variant="tonal"
                        @click="confirmDeleteDialog = false"
                    >
                        Cancel
                    </VBtn>
                    <VBtn color="error" @click="deleteShiftLog"> Delete </VBtn>
                </VCardActions>
            </VCard>
        </VDialog>

        <!-- Photo Viewer Dialog -->
        <VDialog v-model="photoDialogVisible" max-width="800px">
            <VCard>
                <VCardTitle class="d-flex justify-space-between align-center">
                    <span>{{ photoDialogTitle }}</span>
                    <VBtn
                        icon
                        variant="text"
                        @click="photoDialogVisible = false"
                    >
                        <VIcon icon="tabler-x" />
                    </VBtn>
                </VCardTitle>
                <VCardText class="text-center">
                    <VImg
                        :src="photoDialogSrc"
                        max-height="500px"
                        class="rounded"
                        style="transform: scaleX(-1)"
                    />
                </VCardText>
            </VCard>
        </VDialog>

        <!-- Edit Dialog -->
        <StoreUpdateShiftLogDialog
            v-if="editDialogVisible"
            activator-type=""
            :data="data"
            :selected-site-id="data.site_id"
            :config="config"
            @refresh="handleRefresh"
        />
    </VDialog>
</template>

<script setup>
import { useSnackbarStore } from "@/stores/snackbar"
import { can } from "@layouts/plugins/casl"
import dayjs from "dayjs"
import { defineEmits, defineProps, ref } from "vue"
import shiftLogService from "../../services/shiftLogService.js"
import StoreUpdateShiftLogDialog from "./StoreUpdateShiftLogDialog.vue"

const props = defineProps({
    data: {
        type: Object,
        default: () => ({}),
    },
    config: {
        type: Object,
        default: () => ({
            enable_sites: true,
            enable_breaks: false,
            enable_overtime: false,
            enable_photos: false,
            enable_comments: true,
            enable_ip_tracking: false,
            labels: {
                shift: "Shift",
                check_in: "Check In",
                check_out: "Check Out",
                overtime: "Overtime",
                grouping_label: "Site",
            },
        }),
    },
})

const emit = defineEmits(["refresh"])

const snackbar = useSnackbarStore()

// Reactive state
const isDialogVisible = ref(false)
const confirmDeleteDialog = ref(false)
const editDialogVisible = ref(false)
const photoDialogVisible = ref(false)
const photoDialogSrc = ref("")
const photoDialogTitle = ref("")

// Expose the dialog visibility for parent components
defineExpose({
    isDialogVisible,
})

// Methods
const closeDialog = () => {
    isDialogVisible.value = false
}

const openEditDialog = () => {
    editDialogVisible.value = true
    isDialogVisible.value = false
}

const handleRefresh = () => {
    emit("refresh")
    editDialogVisible.value = false
}

const confirmDelete = () => {
    confirmDeleteDialog.value = true
}

const deleteShiftLog = async () => {
    try {
        await shiftLogService.deleteShiftLog(props.data.id)

        confirmDeleteDialog.value = false
        isDialogVisible.value = false
        emit("refresh")
        snackbar.show("Shift log deleted successfully", "success")
    } catch (error) {
        console.error("Error deleting shift log:", error)
        snackbar.show("Failed to delete shift log", "error")
    }
}

const openPhotoDialog = (src, title) => {
    photoDialogSrc.value = src
    photoDialogTitle.value = title
    photoDialogVisible.value = true
}

// Utility functions
const formatDateTime = dateTimeString => {
    return dateTimeString
        ? dayjs(dateTimeString).format("ddd, MMM D, YYYY @ h:mm A")
        : "--"
}

const formatTime = timeString => {
    return timeString ? dayjs(timeString).format("h:mm A") : "--"
}

const formatOvertimeMinutes = minutes => {
    if (!minutes) return "--"
    const hours = Math.floor(minutes / 60)
    const remainingMinutes = minutes % 60
    
    return `${hours}h ${remainingMinutes}m`
}

const calculateTotalTime = () => {
    const start = props.data.claimed_start
    const end = props.data.claimed_end

    if (!start || !end) return "--"

    const duration = dayjs(end).diff(dayjs(start), "minutes")
    const hours = Math.floor(duration / 60)
    const minutes = duration % 60

    return `${hours}h ${minutes}m`
}

const calculateRosteredTime = () => {
    const start = props.data.rostered_start
    const end = props.data.rostered_end

    if (!start || !end) return "--"

    const duration = dayjs(end).diff(dayjs(start), "minutes")
    const hours = Math.floor(duration / 60)
    const minutes = duration % 60

    return `${hours}h ${minutes}m`
}

const calculateBreakDuration = (start, end) => {
    if (!start || !end) return "--"

    const duration = dayjs(end).diff(dayjs(start), "minutes")
    const hours = Math.floor(duration / 60)
    const minutes = duration % 60

    return `${hours}h ${minutes}m`
}

const calculateTotalBreakTime = () => {
    const breaks = props.data.breaks || []
    if (!breaks.length) return "--"

    const totalMinutes = breaks.reduce((total, breakItem) => {
        if (breakItem.break_start && breakItem.break_end) {
            return (
                total +
                dayjs(breakItem.break_end).diff(
                    dayjs(breakItem.break_start),
                    "minutes",
                )
            )
        }
        
        return total
    }, 0)

    const hours = Math.floor(totalMinutes / 60)
    const minutes = totalMinutes % 60

    return `${hours}h ${minutes}m`
}

const getTimeVarianceClass = (rosteredTime, claimedTime) => {
    if (!rosteredTime || !claimedTime) return ""

    const rostered = dayjs(rosteredTime)
    const claimed = dayjs(claimedTime)
    const diff = claimed.diff(rostered, "minutes")

    if (Math.abs(diff) > 5) {
        return diff > 0 ? "text-error" : "text-warning"
    }

    return ""
}

const getTimeVarianceMessage = () => {
    const messages = []

    // Check start time variance
    if (props.data.rostered_start && props.data.claimed_start) {
        const rosteredStart = dayjs(props.data.rostered_start)
        const claimedStart = dayjs(props.data.claimed_start)
        const startDiff = claimedStart.diff(rosteredStart, "minutes")

        if (startDiff > 5) {
            messages.push(`Started ${startDiff} minutes late`)
        } else if (startDiff < -5) {
            messages.push(`Started ${Math.abs(startDiff)} minutes early`)
        }
    }

    // Check end time variance
    if (props.data.rostered_end && props.data.claimed_end) {
        const rosteredEnd = dayjs(props.data.rostered_end)
        const claimedEnd = dayjs(props.data.claimed_end)
        const endDiff = claimedEnd.diff(rosteredEnd, "minutes")

        if (endDiff > 5) {
            messages.push(`Ended ${endDiff} minutes late`)
        } else if (endDiff < -5) {
            messages.push(`Ended ${Math.abs(endDiff)} minutes early`)
        }
    }

    return messages.join(", ")
}

const getTimeVarianceColor = () => {
    if (getTimeVarianceMessage().includes("late")) {
        return "error"
    }
    if (getTimeVarianceMessage().includes("early")) {
        return "warning"
    }
    
    return "info"
}

const getOvertimeStatusColor = response => {
    if (response === null) return "warning"
    if (response === "A") return "success"
    if (response === "R") return "error"
    
    return "secondary"
}

const getOvertimeStatusText = response => {
    if (response === null) return "Pending"
    if (response === "A") return "Approved"
    if (response === "R") return "Declined"
    
    return "Unknown"
}
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}

.font-family-monospace {
    font-family: "Courier New", monospace;
}
</style>
