<template>
    <VDialog v-model="isDialogVisible" max-width="500">
        <!-- Dialog close btn -->
        <DialogCloseBtn @click="closeDialog" />

        <!-- Dialog Content -->
        <VCard title="Copy Day">
            <VCardText v-if="loading">
                <div class="d-flex justify-center my-4">
                    <VProgressCircular indeterminate />
                </div>
            </VCardText>

            <VCardText v-else>
                <VRow>
                    <VCol cols="12">
                        <AppSelect
                            v-model="selectedTargetDays"
                            :items="availableDays"
                            :error="Boolean(errors.targetDays?.length)"
                            :error-messages="errors.targetDays"
                            item-title="name"
                            item-value="index"
                            placeholder="Select target days"
                            label="Copy to days"
                            chips
                            multiple
                            closable-chips
                        >
                            <template #item="{ props, item }">
                                <VListItem
                                    v-bind="{
                                        ...props,
                                        title: null,
                                    }"
                                    :disabled="
                                        item.raw.index === currentSourceDayIndex
                                    "
                                >
                                    <VListItemTitle>
                                        {{ item.raw.name }}
                                        <span
                                            v-if="
                                                item.raw.index ===
                                                    currentSourceDayIndex
                                            "
                                            class="text-caption text-disabled ml-2"
                                        >
                                            (Current day)
                                        </span>
                                    </VListItemTitle>
                                </VListItem>
                            </template>
                        </AppSelect>
                    </VCol>
                </VRow>
            </VCardText>

            <VCardText
                v-if="!loading"
                class="d-flex justify-end flex-wrap gap-3"
            >
                <VBtn variant="tonal" color="secondary" @click="closeDialog">
                    Cancel
                </VBtn>
                <VBtn @click="showConfirmDialog">Copy Day</VBtn>
            </VCardText>
        </VCard>
    </VDialog>
    <ConfirmDialog
        v-model:is-dialog-visible="isConfirmDialogVisible"
        question="This action will clear the selected target days and copy all roster data from the source day. Are you sure you want to continue?"
        @confirm="submit"
        @update:is-dialog-visible="isConfirmDialogVisible = false"
    />
</template>

<script setup>
const props = defineProps({
    isDialogVisible: {
        type: Boolean,
        default: false,
    },
    sourceDayIndex: {
        type: Number,
        default: 0,
    },
    sourceDayName: {
        type: String,
        default: "",
    },
    selectedSiteId: {
        type: [Number, String],
        default: null,
    },
    weekStart: {
        type: [String, Date],
        default: "",
    },
    weekEnd: {
        type: [String, Date],
        default: "",
    },
})

const emit = defineEmits(["update:isDialogVisible", "getRoster"])

const isDialogVisible = computed({
    get: () => props.isDialogVisible,
    set: value => emit("update:isDialogVisible", value),
})

const isConfirmDialogVisible = ref(false)
const selectedTargetDays = ref([])
const loading = ref(false)
const errors = ref({})
const currentSourceDayIndex = ref(0)
const currentSourceDayName = ref("")

import { useSnackbarStore } from "@/stores/snackbar"
import { $api } from "@/utils/api"
import { $endpoint } from "../../utils/endpoints"

const snackbar = useSnackbarStore()

// Generate available days (excluding the source day)
const availableDays = computed(() => {
    return [
        { name: "Monday", index: 0 },
        { name: "Tuesday", index: 1 },
        { name: "Wednesday", index: 2 },
        { name: "Thursday", index: 3 },
        { name: "Friday", index: 4 },
        { name: "Saturday", index: 5 },
        { name: "Sunday", index: 6 },
    ]
})

const submit = async () => {
    if (!validate()) {
        return snackbar.show("Please select at least one target day", "error")
    }

    loading.value = true
    isConfirmDialogVisible.value = false

    try {
        // Copy to each selected day
        await $api($endpoint("COPY_DAY_ROSTER"), {
            method: "POST",
            body: {
                site_id: props.selectedSiteId,
                source_day_index: currentSourceDayIndex.value,
                copy_to: selectedTargetDays.value,
                week_start: getDate(props.weekStart),
                week_end: getDate(props.weekEnd),
            },
            onResponseError({ response }) {
                if (response._data.errors) {
                    errors.value = response._data.errors
                }
                if (response._data.message) {
                    snackbar.show(response._data.message, "error")
                }
            },
        })

        emit("getRoster")
        snackbar.show("Day copied successfully to selected days", "success")
        closeDialog()
    } catch (err) {
        console.error(err)
        snackbar.show("Failed to copy day", "error")
        isConfirmDialogVisible.value = false
    } finally {
        loading.value = false
    }
}

const validate = () => {
    errors.value = {}

    if (selectedTargetDays.value.length === 0) {
        errors.value.targetDays = ["Please select at least one target day"]

        return false
    }

    return true
}

const showConfirmDialog = () => {
    if (validate()) {
        isConfirmDialogVisible.value = true
    }
}

const closeDialog = () => {
    emit("update:isDialogVisible", false)
    selectedTargetDays.value = []
    errors.value = {}
    isConfirmDialogVisible.value = false
}

const getDate = date => {
    return `${date.getFullYear()}-${`0${date.getMonth() + 1}`.slice(
        -2,
    )}-${`0${date.getDate()}`.slice(-2)}`
}

// Expose methods for parent component
defineExpose({
    open: (dayIndex = props.sourceDayIndex, dayName = props.sourceDayName) => {
        // Update the source day info when opening
        currentSourceDayIndex.value = dayIndex
        currentSourceDayName.value = dayName
        emit("update:isDialogVisible", true)
    },
    close: closeDialog,
})
</script>
