<template>
    <VDialog
        :model-value="isDialogVisible"
        max-width="600"
        @update:model-value="$emit('update:is-dialog-visible', $event)"
    >
        <VCard>
            <VCardTitle class="d-flex justify-space-between align-center">
                <span>My Shift Details</span>
                <VBtn icon variant="text" @click="closeDialog">
                    <VIcon icon="tabler-x" />
                </VBtn>
            </VCardTitle>

            <VDivider />

            <VCardText class="pa-6">
                <div v-if="data && Object.keys(data).length > 0">
                    <!-- Shift Header -->
                    <div class="text-h5 mb-2">
                        {{ data.fname }} {{ data.sname }}
                        <VChip
                            v-if="!data.staff_roster_id"
                            color="warning"
                            size="small"
                            class="ms-2"
                        >
                            Not rostered
                        </VChip>
                    </div>

                    <div v-if="data.rostered_start" class="text-h6 mb-6">
                        {{ formatFullDate(data.rostered_start) }}
                    </div>

                    <!-- Shift Times -->
                    <div class="d-flex flex-column gap-4">
                        <!-- Rostered Times (if available) -->
                        <div v-if="data.staff_roster_id">
                            <VLabel class="text-body-1 font-weight-medium mb-2"
                            >Rostered Times</VLabel
                            >
                            <div class="d-flex justify-space-between mb-1">
                                <span>Start:</span>
                                <span>{{
                                    formatTime(data.rostered_start)
                                }}</span>
                            </div>
                            <div class="d-flex justify-space-between">
                                <span>End:</span>
                                <span>{{ formatTime(data.rostered_end) }}</span>
                            </div>
                            <VDivider class="my-3" />
                        </div>

                        <!-- Logged Times -->
                        <div>
                            <VLabel class="text-body-1 font-weight-medium mb-2"
                            >Logged Times</VLabel
                            >
                            <div class="d-flex justify-space-between mb-1">
                                <span>Check-in:</span>
                                <div class="d-flex align-center gap-2">
                                    <span>{{
                                        formatTime(data.actual_start)
                                    }}</span>
                                    <span
                                        v-if="data.claimed_start_time"
                                        class="text-caption text-medium-emphasis"
                                    >
                                        ({{ data.claimed_start_time }})
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex justify-space-between mb-1">
                                <span>Check-out:</span>
                                <div class="d-flex align-center gap-2">
                                    <span>{{
                                        formatTime(data.actual_end)
                                    }}</span>
                                    <span
                                        v-if="data.claimed_end_time"
                                        class="text-caption text-medium-emphasis"
                                    >
                                        ({{ data.claimed_end_time }})
                                    </span>
                                </div>
                            </div>

                            <!-- Time variance indicators -->
                            <div v-if="getTimeVarianceMessage()" class="mt-2">
                                <VAlert
                                    :color="getTimeVarianceColor()"
                                    variant="tonal"
                                    density="compact"
                                    class="text-caption"
                                >
                                    {{ getTimeVarianceMessage() }}
                                </VAlert>
                            </div>
                        </div>

                        <!-- Break Information (if enabled and available) -->
                        <div
                            v-if="
                                shiftLogConfig.enable_breaks &&
                                    data.breaks &&
                                    data.breaks.length > 0
                            "
                        >
                            <VLabel class="text-body-1 font-weight-medium mb-2"
                            >Breaks</VLabel
                            >
                            <div class="d-flex flex-column gap-2">
                                <div
                                    v-for="(breakItem, index) in data.breaks"
                                    :key="index"
                                    class="d-flex justify-space-between align-center pa-2 rounded border"
                                >
                                    <div>
                                        <div class="text-body-2">
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
                                            calculateDuration(
                                                breakItem.break_start,
                                                breakItem.break_end,
                                            )
                                        }}
                                    </VChip>
                                </div>
                            </div>
                        </div>

                        <!-- Work Summary -->
                        <div class="pa-3 bg-surface-variant rounded">
                            <VLabel class="text-body-1 font-weight-medium mb-2"
                            >Work Summary</VLabel
                            >
                            <div class="d-flex justify-space-between mb-1">
                                <span>Total Time:</span>
                                <span class="font-weight-medium">{{
                                    calculateTotalTime()
                                }}</span>
                            </div>
                            <div
                                v-if="shiftLogConfig.enable_breaks"
                                class="d-flex justify-space-between"
                            >
                                <span>Break Time:</span>
                                <span>{{ calculateTotalBreakTime() }}</span>
                            </div>
                        </div>

                        <!-- Overtime Information (if enabled and available) -->
                        <div
                            v-if="
                                shiftLogConfig.enable_overtime && data.ot_claim
                            "
                        >
                            <VLabel class="text-body-1 font-weight-medium mb-2"
                            >Overtime Request</VLabel
                            >
                            <div class="d-flex flex-column gap-2 pa-3 border rounded"
                            >
                                <div class="d-flex justify-space-between">
                                    <span>Duration:</span>
                                    <span>{{
                                        formatOvertimeMinutes(data.ot_claim)
                                    }}</span>
                                </div>
                                <div
                                    v-if="data.ot_reason"
                                    class="d-flex justify-space-between"
                                >
                                    <span>Reason:</span>
                                    <span class="text-right max-width-200">{{
                                        data.ot_reason
                                    }}</span>
                                </div>
                                <div class="d-flex justify-space-between">
                                    <span>Status:</span>
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
                                    v-if="data.ot_response_notes"
                                    class="d-flex justify-space-between"
                                >
                                    <span>Response Notes:</span>
                                    <span class="text-right max-width-200">{{
                                        data.ot_response_notes
                                    }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Comments (if enabled and available) -->
                        <div
                            v-if="
                                shiftLogConfig.enable_comments &&
                                    (data.checkin_comments ||
                                        data.checkout_comments)
                            "
                        >
                            <VLabel class="text-body-1 font-weight-medium mb-2"
                            >Comments</VLabel
                            >
                            <div class="d-flex flex-column gap-3">
                                <div
                                    v-if="data.checkin_comments"
                                    class="pa-3 border rounded"
                                >
                                    <VLabel class="text-body-2 font-weight-medium mb-1"
                                    >Check-in</VLabel
                                    >
                                    <div class="text-body-2">
                                        {{ data.checkin_comments }}
                                    </div>
                                </div>
                                <div
                                    v-if="data.checkout_comments"
                                    class="pa-3 border rounded"
                                >
                                    <VLabel class="text-body-2 font-weight-medium mb-1"
                                    >Check-out</VLabel
                                    >
                                    <div class="text-body-2">
                                        {{ data.checkout_comments }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Photos (if enabled and available) -->
                        <div
                            v-if="
                                shiftLogConfig.enable_photos &&
                                    (data.check_in_photo || data.check_out_photo)
                            "
                        >
                            <VLabel class="text-body-1 font-weight-medium mb-2"
                            >Photos</VLabel
                            >
                            <div class="d-flex gap-4">
                                <div
                                    v-if="data.check_in_photo"
                                    class="d-flex flex-column align-center"
                                >
                                    <VLabel class="text-caption mb-1"
                                    >Check-in</VLabel
                                    >
                                    <VImg
                                        :src="data.check_in_photo"
                                        width="120"
                                        height="90"
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
                                    <VLabel class="text-caption mb-1"
                                    >Check-out</VLabel
                                    >
                                    <VImg
                                        :src="data.check_out_photo"
                                        width="120"
                                        height="90"
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
                        </div>
                    </div>
                </div>
            </VCardText>

            <VDivider />

            <VCardActions class="justify-end">
                <VBtn variant="tonal" color="secondary" @click="closeDialog">
                    Close
                </VBtn>
            </VCardActions>
        </VCard>

        <!-- Photo Viewer Dialog -->
        <VDialog v-model="photoDialogVisible" max-width="600px">
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
                        max-height="400px"
                        class="rounded"
                        style="transform: scaleX(-1)"
                    />
                </VCardText>
            </VCard>
        </VDialog>
    </VDialog>
</template>

<script setup>
import dayjs from "dayjs"
import { ref } from "vue"

const props = defineProps({
    data: {
        type: Object,
        required: true,
        default: () => ({}),
    },
    isDialogVisible: {
        type: Boolean,
        required: false,
        default: false,
    },
})

const emit = defineEmits(["update:is-dialog-visible"])

// Configuration (would be loaded from backend)
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

// Photo dialog state
const photoDialogVisible = ref(false)
const photoDialogSrc = ref("")
const photoDialogTitle = ref("")

// Methods
const closeDialog = () => {
    emit("update:is-dialog-visible", false)
}

const openPhotoDialog = (src, title) => {
    photoDialogSrc.value = src
    photoDialogTitle.value = title
    photoDialogVisible.value = true
}

// Utility functions
const formatFullDate = dateString => {
    if (!dateString) return ""
    
    return dayjs(dateString).format("dddd, MMMM D, YYYY")
}

const formatTime = timeString => {
    if (!timeString) return "--"
    
    return dayjs(timeString).format("h:mm A")
}

const formatOvertimeMinutes = minutes => {
    if (!minutes) return "--"
    const hours = Math.floor(minutes / 60)
    const remainingMinutes = minutes % 60
    
    return `${hours}h ${remainingMinutes}m`
}

const calculateDuration = (start, end) => {
    if (!start || !end) return "--"

    const duration = dayjs(end).diff(dayjs(start), "minutes")
    const hours = Math.floor(duration / 60)
    const minutes = duration % 60

    return hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`
}

const calculateTotalTime = () => {
    const start = props.data.claimed_start || props.data.actual_start
    const end = props.data.claimed_end || props.data.actual_end

    if (!start || !end) {
        // If shift is in progress, calculate time so far
        if (start) {
            const duration = dayjs().diff(dayjs(start), "minutes")
            const hours = Math.floor(duration / 60)
            const minutes = duration % 60
            
            return `${hours}h ${minutes}m (in progress)`
        }
        
        return "--"
    }

    return calculateDuration(start, end)
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

    return hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`
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
    const message = getTimeVarianceMessage()
    if (message.includes("late")) return "error"
    if (message.includes("early")) return "warning"
    
    return "info"
}

const getOvertimeStatusColor = response => {
    if (response === null || response === undefined) return "warning"
    if (response === "A") return "success"
    if (response === "R") return "error"
    
    return "secondary"
}

const getOvertimeStatusText = response => {
    if (response === null || response === undefined) return "Pending"
    if (response === "A") return "Approved"
    if (response === "R") return "Declined"
    
    return "Unknown"
}
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}

.max-width-200 {
    max-width: 200px;
    word-wrap: break-word;
}
</style>
