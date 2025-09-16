<template>
    <VDialog v-model="isDialogVisible" max-width="600">
        <!-- Dialog Activator -->
        <template #activator="{ props }">
            <VBtn
                v-if="activatorType === 'button'"
                v-bind="props"
                color="secondary"
            >
                {{ activatorText || "Copy..." }}
            </VBtn>
            <VListItem v-if="activatorType === 'list-item'" v-bind="props" link>
                <template #prepend>
                    <VIcon icon="tabler-pencil" />
                </template>
                <VListItemTitle>Edit</VListItemTitle>
            </VListItem>
        </template>

        <!-- Dialog close btn -->
        <DialogCloseBtn @click="closeDialog" />

        <!-- Dialog Content -->
        <VCard title="Copy Roster">
            <VCardText v-if="loading">
                <div class="d-flex justify-center my-4">
                    <VProgressCircular indeterminate />
                </div>
            </VCardText>

            <VCardText v-else>
                <VRow>
                    <VCol cols="12">
                        <AppTextField
                            v-model="numberOfWeeks"
                            label="Copy to N Weeks"
                            placeholder="Number of Weeks"
                            type="number"
                            :error="Boolean(errors.numberOfWeeks?.length)"
                            :error-messages="errors.numberOfWeeks"
                            :disabled="selectedWeeks.length > 0"
                            @update:model-value="handleNumberOfWeeksChange"
                        />
                    </VCol>
                    <VCol cols="12">
                        <AppSelect
                            :key="formKey"
                            v-model="selectedWeeks"
                            :items="weeks"
                            :error="Boolean(errors.weeks?.length)"
                            :error-messages="errors.weeks"
                            item-title="title"
                            item-value="n"
                            placeholder="Select Weeks"
                            label="Copy to Weeks"
                            chips
                            multiple
                            closable-chips
                            :disabled="numberOfWeeks !== ''"
                            @update:model-value="handleSelectedWeeksChange"
                        />
                    </VCol>
                </VRow>
            </VCardText>

            <VCardText
                v-if="!loading"
                class="d-flex justify-end flex-wrap gap-3"
            >
                <VBtn variant="tonal" color="secondary" @click="closeDialog">
                    Close
                </VBtn>
                <VBtn @click="showConfirmDialog"> Save </VBtn>
            </VCardText>
        </VCard>
    </VDialog>
    <ConfirmDialog
        v-model:is-dialog-visible="isConfirmDialogVisible"
        question="This action will remove roster data for selected weeks and then copy roster data from current week, are you sure that you want to continue?"
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
    activatorType: {
        type: String,
        default: "button",
    },
    activatorText: {
        type: String,
        default: "Copy...",
    },
    data: {
        type: Object,
        default: () => {},
    },
    weeks: {
        type: Array,
        default: () => [],
    },
    weekStart: {
        type: [String, Date],
        default: "",
    },
    weekEnd: {
        type: [String, Date],
        default: "",
    },
    selectedSiteId: {
        type: [Number, String],
        default: null,
    },
})

const emit = defineEmits(["update:isDialogVisible", "getRoster"])

const isDialogVisible = computed({
    get: () => props.isDialogVisible,
    set: value => emit("update:isDialogVisible", value),
})

const isConfirmDialogVisible = ref(false)
const selectedWeeks = ref([])
const numberOfWeeks = ref("")
const loading = ref(false)
const errors = ref({})
const formKey = ref(0)

import { useSnackbarStore } from "@/stores/snackbar"
import { $api } from "@/utils/api"
import { $endpoint } from "../../utils/endpoints"

const snackbar = useSnackbarStore()

const handleNumberOfWeeksChange = value => {
    if (value !== "") {
        selectedWeeks.value = []
    }
}

const handleSelectedWeeksChange = value => {
    if (value.length > 0) {
        numberOfWeeks.value = ""
    }
}

watch(isDialogVisible, val => {
    if (val && props.data?.id) {
        formData.value.staff = props.data.staff
    }
})

const emptyRoster = {
    staff: "",
    rostered_start: "",
    rostered_end: "",
}

const formData = ref({
    ...emptyRoster,
})

const submit = async () => {
    if (!validate()) {
        return snackbar.show("Invalid data", "error")
    } else {
        await copyRoster(true)
        isConfirmDialogVisible.value = false
    }
}

const copyRoster = async confirm => {
    if (validate() && confirm) {
        let wks = []

        if (numberOfWeeks.value !== "") {
            // Generate weeks based on number
            const numWeeks = parseInt(numberOfWeeks.value)
            for (let i = 1; i <= numWeeks; i++) {
                const startDate = new Date(props.weekStart)

                startDate.setDate(startDate.getDate() + i * 7)

                const endDate = new Date(startDate)

                endDate.setDate(endDate.getDate() + 6)

                wks.push({
                    start: getDate(startDate),
                    end: getDate(endDate),
                })
            }
        } else {
            // Use selected weeks
            selectedWeeks.value.forEach(w => {
                const foundWeek = props.weeks.find(wk => wk.n === w)
                if (foundWeek) {
                    wks.push(foundWeek)
                }
            })
        }

        loading.value = true

        try {
            await $api($endpoint("COPY_STAFF_ROSTER"), {
                method: "POST",
                body: {
                    site_id: props.selectedSiteId,
                    current_week: {
                        start: getDate(props.weekStart),
                        end: getDate(props.weekEnd),
                    },
                    copy_to: wks,
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
            snackbar.show("Roster copied successfully", "success")
            closeDialog()
        } catch (err) {
            console.error(err)
            snackbar.show("Failed to copy roster", "error")
            isConfirmDialogVisible.value = false
        } finally {
            loading.value = false
        }
    }
}

const validate = () => {
    errors.value = {}

    if (selectedWeeks.value.length === 0 && numberOfWeeks.value === "") {
        errors.value.weeks = [
            "Please select specific weeks or enter the number of weeks.",
        ]

        return false
    }

    if (
        numberOfWeeks.value !== "" &&
        (isNaN(numberOfWeeks.value) || parseInt(numberOfWeeks.value) <= 0)
    ) {
        errors.value.numberOfWeeks = ["Please enter a valid positive number."]

        return false
    }

    formKey.value++

    return !Object.keys(errors.value).length
}

const getDate = date => {
    return `${date.getFullYear()}-${`0${date.getMonth() + 1}`.slice(
        -2,
    )}-${`0${date.getDate()}`.slice(-2)}`
}

const showConfirmDialog = () => {
    if (validate()) {
        isConfirmDialogVisible.value = true
    }
}

const closeDialog = () => {
    emit("update:isDialogVisible", false)
    formData.value = { ...emptyRoster }
    errors.value = {}
    formKey.value++
    selectedWeeks.value = []
    numberOfWeeks.value = ""
    isConfirmDialogVisible.value = false
}
</script>
