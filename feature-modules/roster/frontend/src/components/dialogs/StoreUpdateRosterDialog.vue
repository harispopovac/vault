<template>
    <VDialog
        :model-value="props.isDialogVisible"
        persistent
        no-click-animation
        max-width="600"
    >
        <!-- Dialog close btn -->
        <DialogCloseBtn @click="closeDialog" />

        <!-- Dialog Content -->
        <VCard
            :title="
                props.data && props.data.staff_roster_id
                    ? 'Edit Staff Roster'
                    : 'Add Staff Roster'
            "
        >
            <VCardText>
                <VRow>
                    <VCol cols="12" sm="6">
                        <AppAutocomplete
                            v-model="formData.staff_id"
                            v-model:search="search"
                            :loading="loading"
                            :items="staff"
                            :error="Boolean(errors.staff?.length)"
                            :error-messages="errors.staff"
                            item-title="fullname"
                            item-value="id"
                            :multiple="!formData.id"
                            :chips="!formData.id"
                            placeholder="Select Staff Members"
                            label="Assigned Staff"
                            variant="underlined"
                            clearable
                        />
                    </VCol>
                    <VCol v-if="sites.length" cols="12" sm="6">
                        <!-- 👉 Site -->
                        <AppSelect
                            v-model="formData.site_id"
                            :items="sites"
                            item-title="name"
                            item-value="id"
                            label="Site"
                            placeholder="Select Site"
                            style="min-width: 200px"
                        />
                    </VCol>
                    <VCol cols="12" sm="6">
                        <AppDateTimePicker
                            :key="formKey"
                            v-model="formData.rostered_start"
                            :config="{
                                enableTime: true,
                                altInput: true,
                                altFormat: 'd M Y @ h:i K',
                                firstDayOfWeek: 1,
                            }"
                            :error="Boolean(errors.rostered_start?.length)"
                            :error-messages="errors.rostered_start"
                            label="Rostered Start"
                            placeholder="Select Start Time"
                            @update:model-value="handleStartTimeChange"
                        />
                    </VCol>
                    <VCol cols="12" sm="6">
                        <AppDateTimePicker
                            :key="formKey"
                            v-model="formData.rostered_end"
                            :config="{
                                enableTime: true,
                                altInput: true,
                                altFormat: 'd M Y @ h:i K',
                                firstDayOfWeek: 1,
                            }"
                            :error="Boolean(errors.rostered_end?.length)"
                            :error-messages="errors.rostered_end"
                            label="Rostered End"
                            placeholder="Select End Time"
                            @update:model-value="handleEndTimeChange"
                        />
                    </VCol>
                    <VCol cols="12">
                        <VSwitch
                            v-if="props.data?.staff_roster_id"
                            v-model="formData.absent"
                            :inset="false"
                            label="Absent"
                        />
                        <AppTextarea
                            v-if="
                                formData.absent && props.data?.staff_roster_id
                            "
                            :key="formKey"
                            v-model="formData.absent_notes"
                            :error="Boolean(errors.absent_notes?.length)"
                            :error-messages="errors.absent_notes"
                            :rows="3"
                            auto-grow
                            label="Absent Notice"
                            placeholder="Enter Absent Notice"
                        />
                    </VCol>
                </VRow>
            </VCardText>

            <VCardText class="d-flex justify-space-between align-items-center flex-wrap gap-3"
            >
                <VBtn
                    v-if="props.data?.staff_roster_id"
                    color="error"
                    variant="tonal"
                    @click="showConfirmDialog"
                >Remove
                </VBtn>

                <div class="flex-grow-1"></div>

                <VBtn variant="tonal" color="secondary" @click="closeDialog"
                >Close
                </VBtn>
                <VBtn @click="submit">Save</VBtn>
            </VCardText>
        </VCard>
    </VDialog>
    <ConfirmDialog
        v-model:is-dialog-visible="isConfirmRemoveDialogVisible"
        question="Are you sure you want to remove this roster?"
        @confirm="remove"
    />
</template>

<script setup>
import dayjs from "dayjs"
import { debounce } from "lodash"

const props = defineProps({
    isDialogVisible: {
        type: Boolean,
        required: true,
    },
    item: {
        type: Object,
        default: () => {},
    },
    data: {
        type: Object,
        default: () => {},
    },
    selectedSiteId: {
        type: Number,
    },
    sites: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(["update:isDialogVisible", "getRoster"])

import { useSnackbarStore } from "@/stores/snackbar"
import { $api } from "@/utils/api"
import { $endpoint } from "../../utils/endpoints"

const snackbar = useSnackbarStore()

const isConfirmRemoveDialogVisible = ref(false)
const errors = ref({})
const formKey = ref(0)
const staff = ref([])
const loading = ref(false)
const search = ref("")

const emptyRoster = {
    staff_id: null,
    site_id: null,
    rostered_start: "",
    rostered_end: "",
    id: null,
}

const formData = ref({
    ...emptyRoster,
})

watch(
    () => props.isDialogVisible,
    val => {
        if (val) {
            getStaff()
            formData.value.site_id = props.selectedSiteId || null
            if (props.data) {
                formData.value = props.data
                formData.value.id = props.data.staff_roster_id
            }
        }
    },
)

watch(
    search,
    debounce(() => {
        getStaff()
    }, 300),
)

const submit = async () => {
    if (!validate()) {
        return snackbar.show("Invalid data", "error")
    } else {
        if (props.data?.staff_roster_id) {
            await update()
        } else {
            await store()
        }
    }
}

const store = async () => {
    try {
        const response = await $api($endpoint("STORE_STAFF_ROSTER"), {
            method: "POST",
            body: {
                ...formData.value,

                // formData.value.staff_id is actually array on create
                staff_ids: formData.value.staff_id,
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

        if (response.errors) return

        emit("getRoster")
        closeDialog()
    } catch (error) {
        console.log(error)
    }
}

const update = async () => {
    try {
        const response = await $api(
            $endpoint("UPDATE_STAFF_ROSTER", {
                id: props.data.staff_roster_id,
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

        emit("getRoster") // Not updating
        closeDialog()
    } catch (error) {
        console.log(error)
    }
}

const remove = async () => {
    try {
        const response = await $api(
            $endpoint("DELETE_STAFF_ROSTER", {
                id: props.data.staff_roster_id,
            }),
            {
                method: "DELETE",
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

        emit("getRoster") // Not updating
        snackbar.show("Roster removed successfully", "success")
        closeDialog()
    } catch (error) {
        console.log(error)
        snackbar.show("Failed to remove roster", "error")
    } finally {
        isConfirmRemoveDialogVisible.value = false
    }
}

const validate = () => {
    errors.value = {}

    if (!formData.value.staff_id)
        errors.value.staff_id = ["Staff member is required"]
    if (!formData.value.rostered_start)
        errors.value.rostered_start = ["Rostered start time is required"]
    if (!formData.value.rostered_end)
        errors.value.rostered_end = ["Rostered end time is required"]

    formKey.value++

    return !Object.keys(errors.value).length
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

const formatDate = date => {
    return dayjs(date).isValid()
        ? dayjs(date).format("YYYY-MM-DD HH:mm")
        : null
}

const handleStartTimeChange = () => {
    if (
        formData.value.rostered_start &&
        !props.data?.rostered_start &&
        !formData.value.rostered_end
    ) {
        const newEndTime = dayjs(formData.value.rostered_start).add(1, "hour")

        formData.value.rostered_end = formatDate(newEndTime)
    }
}

const handleEndTimeChange = () => {
    if (
        formData.value.rostered_end &&
        !props.data?.rostered_end &&
        !formData.value.rostered_start
    ) {
        const newStartTime = dayjs(formData.value.rostered_end).subtract(
            1,
            "hour",
        )

        formData.value.rostered_start = formatDate(newStartTime)
    }
}

const showConfirmDialog = () => {
    isConfirmRemoveDialogVisible.value = true
}

const closeDialog = () => {
    emit("update:isDialogVisible")
    formData.value = { ...emptyRoster }
    errors.value = {}
    formKey.value++
    isConfirmRemoveDialogVisible.value = false
}
</script>
