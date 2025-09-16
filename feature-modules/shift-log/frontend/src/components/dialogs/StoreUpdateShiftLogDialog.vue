<template>
    <VDialog
        v-model="isDialogVisible"
        persistent
        no-click-animation
        max-width="750"
    >
        <!-- Dialog Activator -->
        <template #activator="{ props }">
            <VBtn
                v-if="activatorType === 'button'"
                prepend-icon="tabler-plus"
                min-width="100px"
                v-bind="props"
                @click="handleActivatorClick"
            >
                Check In
            </VBtn>
            <VBtn
                v-if="activatorType === 'check-in'"
                size="small"
                min-width="100px"
                v-bind="props"
                @click="handleActivatorClick"
            >
                Check In
            </VBtn>
            <VBtn
                v-if="activatorType === 'check-out'"
                size="small"
                min-width="100px"
                v-bind="props"
                @click="handleActivatorClick"
            >
                Check Out
            </VBtn>
            <VListItem
                v-if="activatorType === 'check-in-list'"
                v-bind="props"
                @click="handleActivatorClick"
            >
                <VListItemTitle>Check In</VListItemTitle>
            </VListItem>
            <VListItem
                v-if="activatorType === 'check-out-list'"
                v-bind="props"
                @click="handleActivatorClick"
            >
                <VListItemTitle>Check Out</VListItemTitle>
            </VListItem>
            <VBtn
                v-if="activatorType === 'check-in-header'"
                size="extra-small"
                variant="tonal"
                color="success"
                icon="tabler-player-play"
                rounded
                v-bind="props"
                @click="handleActivatorClick"
            >
            </VBtn>
            <VBtn
                v-if="activatorType === 'check-out-header'"
                size="extra-small"
                variant="tonal"
                color="error"
                icon="tabler-player-stop-filled"
                rounded
                v-bind="props"
                @click="handleActivatorClick"
            />
        </template>

        <!-- Dialog close btn -->
        <DialogCloseBtn @click="closeDialog" />

        <!-- Dialog Content -->
        <VCard :title="dialogTitle">
            <!-- Warning message for forced checkout -->
            <VCardText v-if="isForceCheckout" class="pb-0">
                <VAlert type="warning" prominent border="start" class="mb-4">
                    <VAlertTitle
                    >NOTE: You have an existing open shift, which is shown
                        below.</VAlertTitle
                    >
                    <div>
                        You must check out of that shift before you can check in
                        to new one.
                    </div>
                </VAlert>
            </VCardText>

            <VCardText>
                <VRow>
                    <VCol
                        v-if="
                            displayShiftData?.id ||
                                displayShiftData?.staff_roster_id
                        "
                        cols="12"
                    >
                        <VLabel>
                            {{ displayShiftData.rostered_start_full_date }} @
                            {{ displayShiftData.rostered_start_time }} -
                            {{ displayShiftData.rostered_end_time }}
                        </VLabel>
                    </VCol>
                    <VCol
                        v-if="
                            (!displayShiftData || !displayShiftData.id) &&
                                !displayShiftData?.staff_roster_id
                        "
                        cols="12"
                    >
                        <AppAutocomplete
                            v-model="formData.staff_id"
                            v-model:search="search"
                            :loading="loading"
                            :items="staff"
                            :error="Boolean(errors.staff_id?.length)"
                            :error-messages="errors.staff_id"
                            item-title="fullname"
                            item-value="id"
                            placeholder="Select Staff Members"
                            label="Assigned Staff"
                            variant="underlined"
                            :disabled="props.isClockInOverlay || props.myShifts"
                            clearable
                        />
                    </VCol>

                    <!-- Check-in Section (Left) -->
                    <VCol cols="12" sm="6">
                        <AppDateTimePicker
                            :key="`checkin-time-${formKey}`"
                            v-model="formData.claimed_start"
                            :config="{
                                enableTime: true,
                                altInput: true,
                                altFormat: 'd M Y @ h:i K',
                                firstDayOfWeek: 1,
                            }"
                            :disabled="
                                !!(
                                    displayShiftData &&
                                    displayShiftData.claimed_start
                                ) ||
                                    effectiveActivatorType === 'check-out' ||
                                    (props.myShifts &&
                                        displayShiftData &&
                                        !!displayShiftData.rostered_start)
                            "
                            :error="Boolean(errors.claimed_start?.length)"
                            :error-messages="errors.claimed_start"
                            label="Check In Time"
                            placeholder="Select Check In Time"
                        />

                        <AppTextarea
                            :key="`checkin-comments-${formKey}`"
                            v-model="formData.checkin_comments"
                            :error="Boolean(errors.checkin_comments?.length)"
                            :error-messages="errors.checkin_comments"
                            :rows="6"
                            :disabled="
                                !!(
                                    displayShiftData &&
                                    displayShiftData.actual_start &&
                                    displayShiftData.actual_end
                                ) || effectiveActivatorType === 'check-out'
                            "
                            auto-grow
                            class="mt-4"
                            label="Check In Notes"
                            placeholder="Enter Check In Notes"
                        />

                        <!-- Check-in Image Upload/Display -->
                        <div class="mt-4">
                            <VLabel class="text-body-2 mb-1"
                            >Check In Image</VLabel
                            >

                            <!-- Show uploaded check-in image when in check-out mode -->
                            <div
                                v-if="
                                    displayShiftData?.check_in_photo &&
                                        (displayShiftData?.actual_start ||
                                            effectiveActivatorType === 'check-out')
                                "
                                class="d-flex justify-center position-relative"
                            >
                                <VImg
                                    style="
                                        max-width: 260px;
                                        transform: scaleX(-1);
                                    "
                                    rounded="sm"
                                    :src="displayShiftData.check_in_photo"
                                    width="290"
                                    height="200"
                                    class="shrink-0"
                                />
                            </div>

                            <!-- Upload control - only visible when checking in -->
                            <div
                                v-else-if="
                                    !displayShiftData?.actual_end &&
                                        !displayShiftData?.check_in_photo &&
                                        !tempImgSrc &&
                                        effectiveActivatorType !== 'check-out'
                                "
                                class="image-upload-box d-flex flex-column align-center justify-center position-relative"
                                @click="openWebcamDialog"
                            >
                                <VIcon
                                    size="40"
                                    icon="tabler-camera"
                                    class="mb-2"
                                    color="primary"
                                />
                                <span class="text-body-2 text-primary"
                                >Click to take photo</span
                                >
                            </div>

                            <!-- Show temporary image during check-in -->
                            <div
                                v-else-if="
                                    tempImgSrc &&
                                        !displayShiftData?.check_in_photo &&
                                        effectiveActivatorType !== 'check-out'
                                "
                                class="d-flex justify-center position-relative"
                            >
                                <VImg
                                    style="
                                        max-width: 260px;
                                        transform: scaleX(-1);
                                    "
                                    rounded="sm"
                                    :src="tempImgSrc"
                                    width="290"
                                    height="200"
                                    class="shrink-0"
                                />
                            </div>

                            <!-- Placeholder when checking out and no check-in image exists -->
                            <div
                                v-else-if="
                                    effectiveActivatorType === 'check-out' &&
                                        !displayShiftData?.check_in_photo
                                "
                                class="image-upload-box d-flex flex-column align-center justify-center position-relative disabled-upload-box"
                            >
                                <VIcon
                                    size="40"
                                    icon="tabler-photo-off"
                                    class="mb-2"
                                    color="disabled"
                                />
                                <span class="text-body-2 text-disabled"
                                >No check-in photo available</span
                                >
                            </div>
                        </div>
                    </VCol>

                    <!-- Check-out Section (Right) -->
                    <VCol cols="12" sm="6">
                        <AppDateTimePicker
                            :key="`checkout-time-${formKey}`"
                            v-model="formData.claimed_end"
                            :config="{
                                enableTime: true,
                                altInput: true,
                                altFormat: 'd M Y @ h:i K',
                                firstDayOfWeek: 1,
                            }"
                            :disabled="
                                !!(
                                    displayShiftData &&
                                    displayShiftData.claimed_end &&
                                    displayShiftData.actual_end
                                ) || !formData.claimed_start
                            "
                            :error="Boolean(errors.claimed_end?.length)"
                            :error-messages="errors.claimed_end"
                            label="Check Out Time"
                            placeholder="Select Check Out Time"
                        />

                        <AppTextarea
                            :key="`checkout-comments-${formKey}`"
                            v-model="formData.checkout_comments"
                            :error="Boolean(errors.checkout_comments?.length)"
                            :error-messages="errors.checkout_comments"
                            :rows="6"
                            :disabled="
                                !!(
                                    displayShiftData &&
                                    displayShiftData.actual_end
                                ) || !formData.claimed_start
                            "
                            auto-grow
                            class="mt-4"
                            label="Check Out Notes"
                            placeholder="Enter Check Out Notes"
                        />

                        <!-- Check-out Image Upload/Display -->
                        <div class="mt-4">
                            <VLabel class="text-body-2 mb-1"
                            >Check Out Image</VLabel
                            >
                            <div
                                v-if="
                                    displayShiftData?.actual_start &&
                                        !displayShiftData?.actual_end &&
                                        !displayShiftData?.check_out_photo &&
                                        !tempImgSrc
                                "
                                class="image-upload-box d-flex flex-column align-center justify-center position-relative"
                                @click="openWebcamDialog"
                            >
                                <VIcon
                                    size="40"
                                    icon="tabler-camera"
                                    class="mb-2"
                                    color="primary"
                                />
                                <span class="text-body-2 text-primary"
                                >Click to take photo</span
                                >
                            </div>
                            <!-- Add placeholder when in check-in mode -->
                            <div
                                v-else-if="!formData.claimed_start"
                                class="image-upload-box d-flex flex-column align-center justify-center position-relative disabled-upload-box"
                            >
                                <VIcon
                                    size="40"
                                    icon="tabler-camera"
                                    class="mb-2"
                                    color="disabled"
                                />
                                <span class="text-body-2 text-disabled"
                                >Available after check-in</span
                                >
                            </div>
                            <div
                                v-else-if="displayShiftData?.actual_start"
                                class="d-flex justify-center position-relative"
                            >
                                <VImg
                                    v-if="
                                        tempImgSrc &&
                                            !displayShiftData?.check_out_photo &&
                                            displayShiftData?.actual_start
                                    "
                                    style="
                                        max-width: 260px;
                                        transform: scaleX(-1);
                                    "
                                    rounded="sm"
                                    :src="tempImgSrc"
                                    width="290"
                                    height="200"
                                    class="shrink-0"
                                />
                                <VImg
                                    v-else-if="
                                        displayShiftData?.check_out_photo
                                    "
                                    style="
                                        max-width: 260px;
                                        transform: scaleX(-1);
                                    "
                                    rounded="sm"
                                    :src="displayShiftData.check_out_photo"
                                    width="290"
                                    height="200"
                                    class="shrink-0"
                                />
                            </div>
                        </div>
                    </VCol>

                    <!-- Shift Breaks Section -->
                    <VCol
                        v-if="
                            displayShiftData?.staff_roster_shift_breaks?.length
                        "
                        cols="12"
                    >
                        <VLabel class="text-body-2 mb-2">Shift Breaks</VLabel>
                        <VTable density="compact" class="border rounded">
                            <thead>
                                <tr>
                                    <th class="text-start">Notes</th>
                                    <th class="text-start">Break Start</th>
                                    <th class="text-start">Break End</th>
                                    <th class="text-start">Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(
                                        break_item, index
                                    ) in displayShiftData.staff_roster_shift_breaks"
                                    :key="index"
                                >
                                    <td>{{ break_item.notes || "-" }}</td>
                                    <td>
                                        {{
                                            formatBreakTime(
                                                break_item.break_start,
                                            )
                                        }}
                                    </td>
                                    <td>
                                        {{
                                            formatBreakTime(
                                                break_item.break_end,
                                            )
                                        }}
                                    </td>
                                    <td>
                                        {{
                                            calculateBreakDuration(
                                                break_item.break_start,
                                                break_item.break_end,
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot
                                v-if="
                                    displayShiftData?.staff_roster_shift_breaks
                                        ?.length > 0
                                "
                            >
                                <tr class="bg-secondary-subtle">
                                    <td
                                        colspan="3"
                                        class="text-end font-weight-bold"
                                    >
                                        Total Duration:
                                    </td>
                                    <td class="font-weight-bold">
                                        {{
                                            calculateTotalBreakDuration(
                                                displayShiftData.staff_roster_shift_breaks,
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tfoot>
                        </VTable>
                    </VCol>

                    <!-- Work Summary Section - Show on checkout -->
                    <VCol
                        v-if="
                            displayShiftData &&
                                displayShiftData.actual_start &&
                                formData.claimed_end
                        "
                        cols="12"
                    >
                        <VCard
                            variant="outlined"
                            class="pa-4 mt-2 work-summary-card"
                        >
                            <VCardTitle class="d-flex align-center mb-4">
                                <VIcon
                                    icon="tabler-clipboard-list"
                                    color="primary"
                                    size="24"
                                    class="me-2"
                                />
                                <span class="text-h6">Work Summary</span>
                            </VCardTitle>
                            <div class="summary-grid">
                                <div class="summary-item">
                                    <div class="summary-icon-wrapper primary">
                                        <VIcon
                                            icon="tabler-clock"
                                            size="24"
                                            class="summary-icon"
                                        />
                                    </div>
                                    <div class="summary-content">
                                        <div class="summary-label">
                                            Total Worked Time
                                        </div>
                                        <div class="summary-value">
                                            {{ calculateTotalWorkedTime() }}
                                        </div>
                                    </div>
                                </div>

                                <div class="summary-item">
                                    <div class="summary-icon-wrapper secondary">
                                        <VIcon
                                            icon="tabler-coffee"
                                            size="24"
                                            class="summary-icon"
                                        />
                                    </div>
                                    <div class="summary-content">
                                        <div class="summary-label">
                                            Total Break Time
                                        </div>
                                        <div class="summary-value">
                                            {{ calculateTotalBreakTime() }}
                                        </div>
                                    </div>
                                </div>

                                <div class="summary-item">
                                    <div class="summary-icon-wrapper success">
                                        <VIcon
                                            icon="tabler-hourglass"
                                            size="24"
                                            class="summary-icon"
                                        />
                                    </div>
                                    <div class="summary-content">
                                        <div class="summary-label">
                                            Total Paid Time
                                        </div>
                                        <div class="summary-value">
                                            {{ calculateTotalPaidTime() }}
                                        </div>
                                    </div>
                                </div>

                                <div v-if="hourlyRate" class="summary-item">
                                    <div class="summary-icon-wrapper info">
                                        <VIcon
                                            icon="tabler-currency-dollar"
                                            size="24"
                                            class="summary-icon"
                                        />
                                    </div>
                                    <div class="summary-content">
                                        <div class="summary-label">
                                            Total Calculated Pay
                                        </div>
                                        <div class="summary-value">
                                            ${{
                                                calculateTotalPay().toFixed(2)
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </VCard>
                    </VCol>

                    <VCol v-if="requestingOvertime" cols="12">
                        <AppTextField
                            v-model="editableDuration"
                            label="Overtime Claim"
                            placeholder="e.g. 2h 30m"
                            :error="Boolean(errors.ot_claim?.length)"
                            :error-messages="errors.ot_claim"
                            @blur="handleDurationBlur"
                        />
                    </VCol>
                    <VCol v-if="requestingOvertime" cols="12">
                        <AppTextarea
                            v-model="formData.ot_reason"
                            label="Overtime Reason"
                            placeholder="Enter Overtime Reason"
                            :error="Boolean(errors.ot_reason?.length)"
                            :error-messages="errors.ot_reason"
                        />
                    </VCol>
                </VRow>
            </VCardText>

            <VCardText class="d-flex justify-space-between align-items-center flex-wrap gap-3"
            >
                <div class="flex-grow-1"></div>

                <VSwitch
                    v-model="requestingOvertime"
                    label="Request Overtime"
                />
                <VBtn variant="tonal" color="secondary" @click="closeDialog"
                >Close</VBtn
                >
                <VBtn @click="submit">Save</VBtn>
            </VCardText>
        </VCard>
    </VDialog>
    <Webcam
        ref="webcamDialog"
        @photo-captured="handlePhotoCaptured"
        @camera-error="handleCameraError"
    />
</template>

<script setup>
import { useSnackbarStore } from "@/stores/snackbar"
import { $api } from "@/utils/api"
import { $endpoint } from "../../utils/endpoints"
import { useCookie } from "@core/composable/useCookie"
import dayjs from "dayjs"
import duration from "dayjs/plugin/duration"
import { debounce } from "lodash"

const props = defineProps({
    activatorType: {
        type: String,
        default: "button",
    },
    data: {
        type: Object,
        default: () => {},
        required: true,
    },
    selectedSiteId: {
        type: Number,
    },
    isClockInOverlay: {
        type: Boolean,
        default: false,
    },
    myShifts: {
        type: Boolean,
        default: false,
    },
    openShift: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits([
    "update:isDialogVisible",
    "getRosterLog",
    "refresh",
    "close",
    "successfulCheckin",
    "checkInClick",
])

// Extend dayjs with duration plugin
dayjs.extend(duration)

const isDialogVisible = ref(false)

// Add a method to open the dialog programmatically
// This allows parent components to open the dialog directly
const openDialog = () => {
    isDialogVisible.value = true
}

// Expose methods that need to be called from parent components
defineExpose({
    openDialog,
})

// const clockInStore = useClockInStore()

const snackbar = useSnackbarStore()

const errors = ref({})
const formKey = ref(0)
const staff = ref([])
const loading = ref(false)
const requestingOvertime = ref(false)
const search = ref("")
const webcamDialog = ref(null)
const tempImgSrc = ref(null)
const capturedPhotoBlob = ref(null)

// Check if this is a forced check-out scenario (user has open shift and clicked check-in)
const isForceCheckout = computed(() => {
    return (
        props.openShift &&
        props.openShift.id !== props.data.id &&
        props.activatorType === "check-in"
    )
})

// Get the shift data to display (either current data or open shift for forced checkout)
const displayShiftData = computed(() => {
    return isForceCheckout.value ? props.openShift : props.data
})

// Override activator type for forced checkout
const effectiveActivatorType = computed(() => {
    return isForceCheckout.value ? "check-out" : props.activatorType
})

// Dialog title based on the scenario
const dialogTitle = computed(() => {
    if (isForceCheckout.value) {
        return `Check out of existing shift! - ${displayShiftData.value.fname} ${displayShiftData.value.sname}`
    }
    
    return `${props.data && props.data.id ? "Rostered Shift" : "Rostered Shift"}${
        props.data.fname
            ? " - " + props.data.fname + " " + props.data.sname
            : ""
    }`
})

// Handle activator click
const handleActivatorClick = () => {
    if (
        props.activatorType === "check-in" &&
        props.openShift &&
        props.openShift.id !== props.data.id
    ) {
        // User has an open shift and clicked check-in on a different shift
        // Open this dialog with the open shift data for checkout
        formData.value = { ...props.openShift }
        if (props.selectedSiteId) {
            formData.value.site_id = props.selectedSiteId
        }
        isDialogVisible.value = true
        
        return
    }

    // Normal dialog opening logic
    isDialogVisible.value = true
}

/**
 * Format break time from ISO string to readable format using dayjs
 */
const formatBreakTime = isoTime => {
    if (!isoTime) return "-"

    return dayjs(isoTime).format("MMM D, YYYY h:mm A")
}

/**
 * Calculate break duration between start and end times using dayjs
 */
const calculateBreakDuration = (start, end) => {
    if (!start || !end) return "-"

    const startTime = dayjs(start)
    const endTime = dayjs(end)

    // Calculate difference in seconds
    const diffSeconds = endTime.diff(startTime, "second")

    // If negative or invalid, return placeholder
    if (isNaN(diffSeconds) || diffSeconds < 0) return "-"

    const hours = Math.floor(diffSeconds / 3600)
    const minutes = Math.floor((diffSeconds % 3600) / 60)
    const seconds = diffSeconds % 60

    return `${hours}h ${minutes}m ${seconds}s`
}

/**
 * Calculate the total duration of all breaks
 */
const calculateTotalBreakDuration = breakItems => {
    if (!breakItems || !breakItems.length) return "-"

    let totalSeconds = 0

    // Sum up all valid break durations
    breakItems.forEach(item => {
        if (item.break_start && item.break_end) {
            const startTime = dayjs(item.break_start)
            const endTime = dayjs(item.break_end)
            const diffSeconds = endTime.diff(startTime, "second")

            if (!isNaN(diffSeconds) && diffSeconds > 0) {
                totalSeconds += diffSeconds
            }
        }
    })

    // Format total duration
    const hours = Math.floor(totalSeconds / 3600)
    const minutes = Math.floor((totalSeconds % 3600) / 60)
    const seconds = totalSeconds % 60

    return `${hours}h ${minutes}m ${seconds}s`
}

const emptyRosterLog = {
    staff_id: props.myShifts ? useCookie("user").value?.id || null : null,
    rostered_start: "",
    rostered_end: "",
    id: null,
    claimed_start: null,
    claimed_end: null,
    ot_claim: 0,
    ot_reason: null,
}

const formData = ref({
    ...emptyRosterLog,
})

watch(isDialogVisible, val => {
    if (val) {
        // Handle forced checkout scenario
        if (isForceCheckout.value) {
            if (props.openShift.ot_claim) {
                requestingOvertime.value = true
            }
            getStaff()
            emit("getRosterLog")
            emit("refresh")
            formData.value = { ...props.openShift }
            if (props.selectedSiteId) {
                formData.value.site_id = props.selectedSiteId
            }
            if (props.myShifts) {
                formData.value.staff_id = useCookie("user").value?.id || null
            }

            // Auto-fill the check-out time for forced checkout
            if (!formData.value.claimed_end) {
                formData.value.claimed_end = dayjs().format("YYYY-MM-DD HH:mm")
            }

            return
        }

        // Normal dialog opening logic
        if (props.data.ot_claim) {
            requestingOvertime.value = true
        }
        getStaff()
        emit("getRosterLog")
        emit("refresh")
        if (props.data) {
            formData.value = { ...props.data }
        }
        if (props.selectedSiteId) {
            formData.value.site_id = props.selectedSiteId
        }

        if (props.myShifts) {
            formData.value.staff_id = useCookie("user").value?.id || null
        }

        // Handle check-in time prefilling
        if (!formData.value.claimed_start) {
            if (formData.value.rostered_start) {
                // For rostered shifts, prefill with rostered start time
                formData.value.claimed_start = formData.value.rostered_start
            } else {
                // For non-rostered shifts, prefill with current time
                formData.value.claimed_start =
                    dayjs().format("YYYY-MM-DD HH:mm")
            }
        }
    }
})

watch(
    search,
    debounce(() => {
        getStaff()
    }, 300),
)

const submit = async () => {
    if (!validate()) {
        console.log(formData.value)

        return snackbar.show(errors, "error")
    } else {
        if (displayShiftData.value?.id) {
            await update()
        } else {
            await store()
        }
    }
}

const store = async () => {
    try {
        // Create FormData to handle both text data and files
        const submitData = new FormData()

        // Add all form fields to FormData
        Object.keys(formData.value).forEach(key => {
            if (
                formData.value[key] !== null &&
                formData.value[key] !== undefined
            ) {
                submitData.append(key, formData.value[key])
            }
        })

        // Add the photo if it exists
        if (capturedPhotoBlob.value) {
            submitData.append(
                "check_in_photo",
                capturedPhotoBlob.value,
                "check_in_photo.png",
            )
        }

        const response = await $api($endpoint("SHIFT_LOG_STORE"), {
            method: "POST",
            body: submitData,
            onResponseError({ response }) {
                if (response._data.errors) {
                    errors.value = response._data.errors
                }

                if (response._data.message) {
                    snackbar.show(response._data.message, "error")
                }
            },
        })

        if (response.errors) return

        // Clear the clock-in restriction if this was a check-in
        if (!props.data || !props.data.actual_start) {
            // Emit a specific event for successful check-in
            emit("successfulCheckin")
        }

        emit("getRosterLog")
        emit("refresh")
        closeDialog()
    } catch (error) {
        console.log(error)
    }
}

const update = async () => {
    try {
        const response = await $api(
            $endpoint("SHIFT_LOG_UPDATE", {
                id: displayShiftData.value.id,
            }),
            {
                method: "PUT",
                body: formData.value,
                onResponseError({ response }) {
                    if (response._data.errors) {
                        errors.value = response._data.errors
                    }

                    if (response._data.message) {
                        snackbar.show(response._data.message, "error")
                    }
                },
            },
        )

        if (response.errors) return

        emit("getRosterLog") // Not updating
        emit("refresh")
        closeDialog()
    } catch (error) {
        console.log(error)
    }
}

const getStaff = async () => {
    loading.value = true

    try {
        const response = await $api(
            $endpoint("GET_STAFF_LIST") + "?all=true&search=" + search.value,
            {
                method: "GET",
                onResponseError() {
                    //
                },
            },
        )

        staff.value = response.staff
    } catch (e) {
        console.log(e)
    } finally {
        loading.value = false
    }
}

const validate = () => {
    errors.value = {}

    if (!formData.value.staff_id && !props.isClockInOverlay)
        errors.value.staff_id = ["Staff member is required"]
    if (!formData.value.claimed_start)
        errors.value.claimed_start = ["Check In time is required"]

    formKey.value++

    return !Object.keys(errors.value).length
}

const openWebcamDialog = () => {
    if (webcamDialog.value) {
        webcamDialog.value.openDialog().catch(error => {
            // Error is already displayed in the webcam component
            console.error("Failed to open camera:", error)
        })
    }
}

async function handlePhotoCaptured(photoData) {
    try {
        // Convert base64 to Blob
        const byteString = atob(photoData.split(",")[1])
        const mimeString = photoData.split(",")[0].split(":")[1].split(";")[0]
        const ab = new ArrayBuffer(byteString.length)
        const ia = new Uint8Array(ab)
        for (let i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i)
        }
        const blob = new Blob([ab], { type: mimeString })

        // Store the blob for later submission
        capturedPhotoBlob.value = blob

        // Create FormData and append the Blob
        const formData = new FormData()

        formData.append("photo", blob, "photo.png")

        if (displayShiftData.value.id) {
            formData.append("id", displayShiftData.value.id)

            // Upload the photo using the API
            try {
                const response = await $api(
                    $endpoint("SHIFT_LOG_UPLOAD_PHOTO"),
                    {
                        method: "POST",
                        body: formData,
                    },
                )

                // Assuming the response contains the URL of the uploaded image
                if (response && response.photoUrl) {
                    tempImgSrc.value = response.photoUrl
                    snackbar.show("Photo uploaded successfully", "success")
                }

                console.log("Photo uploaded successfully:", response)
            } catch (error) {
                console.error("Error uploading photo:", error)
                snackbar.show(
                    "Failed to upload photo: " +
                        (error.response?._data?.message || "Unknown error"),
                    "error",
                )
            }
        } else {
            tempImgSrc.value = URL.createObjectURL(blob)
        }
    } catch (error) {
        console.error("Error processing photo:", error)
        snackbar.show(
            "Failed to process photo: " + (error.message || "Unknown error"),
            "error",
        )
    }
}

// TIME input

const editableDuration = ref("")

const parseDuration = durationStr => {
    const digitsOnly = /^\d+$/.test(durationStr)
    if (digitsOnly) {
        return parseInt(durationStr, 10)
    }
    const matches = durationStr.match(/(\d+)\s*h\s*(\d*)\s*m*/)
    if (matches) {
        const hours = parseInt(matches[1] || "0", 10)
        const minutes = parseInt(matches[2] || "0", 10)

        return hours * 60 + minutes
    }

    return null
}

const formatDuration = minutes => {
    if (minutes == null) return ""

    return dayjs.duration(minutes, "minutes").format("H[h] m[m]")
}

const handleDurationBlur = () => {
    const minutes = parseDuration(editableDuration.value)
    if (minutes !== null) {
        formData.value.ot_claim = minutes
        editableDuration.value = formatDuration(minutes)
    }
}

watch(
    () => formData.value.ot_claim,
    newDuration => {
        editableDuration.value = formatDuration(newDuration)
    },
    { immediate: true },
)

const handleCameraError = error => {
    snackbar.show(
        "Failed to open camera: " + (error.message || "Unknown error"),
        "error",
    )
}

const closeDialog = () => {
    isDialogVisible.value = false
    emit("update:isDialogVisible", false)
    requestingOvertime.value = false
    formData.value = { ...emptyRosterLog }
    errors.value = {}
    tempImgSrc.value = null
    capturedPhotoBlob.value = null
    formKey.value++
}

const hourlyRate = computed(() => {
    // Get the hourly rate from staff data if available
    // This might need to be fetched from API or passed as prop
    if (displayShiftData.value && displayShiftData.value.hourly_rate) {
        return displayShiftData.value.hourly_rate
    }

    return null
})

/**
 * Calculate total worked time between check-in and check-out
 */
const calculateTotalWorkedTime = () => {
    if (!formData.value.claimed_start || !formData.value.claimed_end)
        return "-"

    const startTime = dayjs(formData.value.claimed_start)
    const endTime = dayjs(formData.value.claimed_end)
    const diffMinutes = endTime.diff(startTime, "minute")

    if (isNaN(diffMinutes) || diffMinutes < 0) return "-"

    const hours = Math.floor(diffMinutes / 60)
    const minutes = diffMinutes % 60

    return `${hours}h ${minutes}m`
}

/**
 * Calculate total break time for all breaks
 */
const calculateTotalBreakTime = () => {
    if (
        !displayShiftData.value ||
        !displayShiftData.value.staff_roster_shift_breaks ||
        !displayShiftData.value.staff_roster_shift_breaks.length
    ) {
        return "0h 0m"
    }

    let totalMinutes = 0

    displayShiftData.value.staff_roster_shift_breaks.forEach(breakItem => {
        if (breakItem.break_start && breakItem.break_end) {
            const startTime = dayjs(breakItem.break_start)
            const endTime = dayjs(breakItem.break_end)
            const diffMinutes = endTime.diff(startTime, "minute")

            if (!isNaN(diffMinutes) && diffMinutes > 0) {
                totalMinutes += diffMinutes
            }
        }
    })

    const hours = Math.floor(totalMinutes / 60)
    const minutes = totalMinutes % 60

    return `${hours}h ${minutes}m`
}

/**
 * Calculate total paid time (worked time minus break time)
 */
const calculateTotalPaidTime = () => {
    if (!formData.value.claimed_start || !formData.value.claimed_end)
        return "-"

    // Calculate total worked minutes
    const startTime = dayjs(formData.value.claimed_start)
    const endTime = dayjs(formData.value.claimed_end)
    const totalWorkedMinutes = endTime.diff(startTime, "minute")

    if (isNaN(totalWorkedMinutes) || totalWorkedMinutes < 0) return "-"

    // Calculate total break minutes
    let totalBreakMinutes = 0
    if (
        displayShiftData.value.staff_roster_shift_breaks &&
        displayShiftData.value.staff_roster_shift_breaks.length
    ) {
        displayShiftData.value.staff_roster_shift_breaks.forEach(
            breakItem => {
                if (breakItem.break_start && breakItem.break_end) {
                    const breakStart = dayjs(breakItem.break_start)
                    const breakEnd = dayjs(breakItem.break_end)
                    const diffMinutes = breakEnd.diff(breakStart, "minute")

                    if (!isNaN(diffMinutes) && diffMinutes > 0) {
                        totalBreakMinutes += diffMinutes
                    }
                }
            },
        )
    }

    // Calculate total paid minutes
    const totalPaidMinutes = totalWorkedMinutes - totalBreakMinutes

    const hours = Math.floor(totalPaidMinutes / 60)
    const minutes = totalPaidMinutes % 60

    return `${hours}h ${minutes}m`
}

/**
 * Calculate total pay based on hourly rate and paid time
 */
const calculateTotalPay = () => {
    if (
        !hourlyRate.value ||
        !formData.value.claimed_start ||
        !formData.value.claimed_end
    )
        return 0

    // Calculate total worked minutes
    const startTime = dayjs(formData.value.claimed_start)
    const endTime = dayjs(formData.value.claimed_end)
    const totalWorkedMinutes = endTime.diff(startTime, "minute")

    // Calculate total break minutes
    let totalBreakMinutes = 0
    if (
        displayShiftData.value.staff_roster_shift_breaks &&
        displayShiftData.value.staff_roster_shift_breaks.length
    ) {
        displayShiftData.value.staff_roster_shift_breaks.forEach(
            breakItem => {
                if (breakItem.break_start && breakItem.break_end) {
                    const breakStart = dayjs(breakItem.break_start)
                    const breakEnd = dayjs(breakItem.break_end)
                    const diffMinutes = breakEnd.diff(breakStart, "minute")

                    if (!isNaN(diffMinutes) && diffMinutes > 0) {
                        totalBreakMinutes += diffMinutes
                    }
                }
            },
        )
    }

    // Calculate total paid hours
    const totalPaidMinutes = totalWorkedMinutes - totalBreakMinutes
    const totalPaidHours = totalPaidMinutes / 60

    // Calculate pay including overtime if requested
    let totalPay = totalPaidHours * hourlyRate.value

    // Add overtime pay if applicable
    if (requestingOvertime.value && formData.value.ot_claim) {
        const overtimeHours = formData.value.ot_claim / 60

        totalPay += overtimeHours * hourlyRate.value * 1.5 // Assuming overtime rate is 1.5x
    }

    return totalPay
}
</script>

<style scoped>
.image-upload-box {
    width: 100%;
    height: 158px;
    border: 2px dashed rgba(var(--v-theme-primary), 0.4);
    border-radius: 8px;
    cursor: pointer;
    background-color: rgba(var(--v-theme-primary), 0.05);
    transition: all 0.3s ease;
}

.image-upload-box:hover {
    border-color: rgb(var(--v-theme-primary));
    background-color: rgba(var(--v-theme-primary), 0.1);
}

.disabled-upload-box {
    border: 2px dashed rgba(var(--v-theme-on-surface), 0.2);
    background-color: rgba(var(--v-theme-on-surface), 0.05);
    cursor: default;
    opacity: 0.7;
}

.disabled-upload-box:hover {
    border-color: rgba(var(--v-theme-on-surface), 0.2);
    background-color: rgba(var(--v-theme-on-surface), 0.05);
    transform: none;
}

/* Work Summary Styles */
.work-summary-card {
    border-radius: 12px;
    transition: all 0.3s ease;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.summary-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    background-color: rgba(var(--v-theme-surface), 1);
    transition: all 0.3s ease;
}

.summary-icon-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 12px;
}

.summary-icon-wrapper.primary {
    background-color: rgba(var(--v-theme-primary), 0.15);
    color: rgb(var(--v-theme-primary));
}

.summary-icon-wrapper.secondary {
    background-color: rgba(var(--v-theme-secondary), 0.15);
    color: rgb(var(--v-theme-secondary));
}

.summary-icon-wrapper.success {
    background-color: rgba(var(--v-theme-success), 0.15);
    color: rgb(var(--v-theme-success));
}

.summary-icon-wrapper.info {
    background-color: rgba(var(--v-theme-info), 0.15);
    color: rgb(var(--v-theme-info));
}

.summary-content {
    flex: 1;
}

.summary-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: rgba(var(--v-theme-on-surface), 0.7);
    margin-bottom: 4px;
}

.summary-value {
    font-size: 1.125rem;
    font-weight: 600;
    color: rgba(var(--v-theme-on-surface), 1);
}

@media (max-width: 600px) {
    .summary-grid {
        grid-template-columns: 1fr;
    }
}
</style>
