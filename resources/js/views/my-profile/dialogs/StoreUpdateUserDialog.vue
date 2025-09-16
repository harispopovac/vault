<template>
    <VDialog
        v-model="isDialogVisible"
        persistent
        no-click-animation
        max-width="600"
    >
        <!-- Dialog Activator -->
        <template #activator="{ props }">
            <VBtn
                v-if="activatorType === 'button'"
                prepend-icon="tabler-plus"
                v-bind="props"
            >
                User
            </VBtn>
            <VListItem v-if="activatorType === 'list-item'" v-bind="props" link>
                <template #prepend>
                    <VIcon icon="tabler-pencil" />
                </template>
                <VListItemTitle>Edit</VListItemTitle>
            </VListItem>
            <VBtn
                v-if="activatorType === 'edit-button'"
                variant="elevated"
                v-bind="props"
            >
                Edit
            </VBtn>
        </template>

        <!-- Dialog close btn -->
        <DialogCloseBtn @click="closeDialog" />

        <!-- Dialog Content -->
        <VCard title="User Profile">
            <VCardText>
                <VRow>
                    <VCol
                        cols="12"
                        md="3"
                        class="d-flex justify-center align-end"
                    >
                        <VAvatar
                            rounded
                            :size="120"
                            color="primary"
                            variant="tonal"
                        >
                            <VImg v-if="formData.photo" :src="formData.photo" />
                            <span v-else class="text-5xl font-weight-medium">
                                {{
                                    getUserInitials(
                                        formData.fname,
                                        formData.sname,
                                    )
                                }}
                            </span>

                            <UploadPhoto
                                :model-id="formData.id"
                                endpoint="UPLOAD_STAFF_PHOTO"
                                @update="emit('getUser')"
                                @update-data="updateUserData"
                            />
                        </VAvatar>
                    </VCol>
                    <VCol cols="12" md="9">
                        <VRow>
                            <VCol cols="12" sm="6" md="12">
                                <AppTextField
                                    :key="formKey"
                                    v-model="formData.fname"
                                    :error="Boolean(errors.fname?.length)"
                                    :error-messages="errors.fname"
                                    label="Name"
                                    placeholder="John"
                                />
                            </VCol>
                            <VCol cols="12" sm="6" md="12">
                                <AppTextField
                                    :key="formKey"
                                    v-model="formData.sname"
                                    :error="Boolean(errors.sname?.length)"
                                    :error-messages="errors.sname"
                                    label="Surname"
                                    placeholder="Doe"
                                />
                            </VCol>
                        </VRow>
                    </VCol>
                    <VCol cols="12" sm="6">
                        <AppDateTimePicker
                            :key="formKey"
                            v-model="formData.dob"
                            :config="{
                                enableTime: false,
                                altInput: true,
                                altFormat: 'd M Y',
                                firstDayOfWeek: 1,
                            }"
                            :error="Boolean(errors.dob?.length)"
                            :error-messages="errors.dob"
                            label="Date of Birth"
                            placeholder="Select Date"
                        />
                    </VCol>
                    <VCol cols="12" sm="6">
                        <AppSelect
                            :key="formKey"
                            v-model="formData.gender"
                            :items="[
                                { title: 'Male', value: 'M' },
                                { title: 'Female', value: 'F' },
                            ]"
                            :error="Boolean(errors.gender?.length)"
                            :error-messages="errors.gender"
                            label="Gender"
                            placeholder="Select Gender"
                        />
                    </VCol>
                    <VCol cols="12" md="6">
                        <AppTextField
                            :key="formKey"
                            v-model="formData.phone"
                            :error="Boolean(errors.phone?.length)"
                            :error-messages="errors.phone"
                            label="Phone"
                            placeholder="Enter Phone Number"
                        />
                    </VCol>
                    <VCol cols="12" md="6">
                        <div class="d-flex align-end gap-2">
                            <AppTextField
                                v-model="formData.email"
                                readonly
                                label="Email"
                                placeholder="johndoe@email.com"
                            />
                            <VIcon
                                class="mb-2"
                                @click="isInfoEmailDialogVisible = true"
                            >tabler-replace</VIcon
                            >
                        </div>
                        <InfoEmailDialog @update-user-email="handleUpdateUserEmail"
                        />
                    </VCol>
                </VRow>
            </VCardText>
            <VCardText class="d-flex justify-end flex-wrap gap-3">
                <VBtn variant="tonal" color="secondary" @click="closeDialog">
                    Close
                </VBtn>
                <VBtn @click="submit"> Save </VBtn>
            </VCardText>
        </VCard>
    </VDialog>
</template>

<script setup>
import InfoEmailDialog from "./InfoEmailDialog.vue"

const props = defineProps({
    activatorType: {
        type: String,
        default: "button",
    },
    data: {
        type: Object,
        default: () => {},
    },
    me: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(["getUser"])

const isInfoEmailDialogVisible = ref(false)

provide("isInfoEmailDialogVisible", isInfoEmailDialogVisible)

const isDialogVisible = ref(false)

import { useSnackbarStore } from "@/stores/snackbar"

const snackbar = useSnackbarStore()

watch(isDialogVisible, val => {
    if (val && props.data?.id) {
        formData.value = {
            ...props.data,
        }
    }
})

const emptyUser = {
    fname: "",
    sname: "",
    dob: "",
    phone: "",
    email: "",
}

const formData = ref({
    ...emptyUser,
})

const updateUserData = data => {
    formData.value = {
        ...data,
    }

    const me = useCookie("user").value
    if (me.id === data.id) {
        useCookie("user").value = JSON.stringify(data)
    }
}

const submit = async () => {
    if (!validate()) {
        return snackbar.show("Invalid data", "error")
    }

    if (formData.value.id) {
        await update()
    } else {
        await store()
    }
}

// TODO: Delete
const store = async () => {
    try {
        const response = await $api($endpoint("STORE_USER"), {
            method: "POST",
            body: formData.value,
            onResponseError({ response }) {
                if (response._data.errors) {
                    errors.value = response._data.errors
                }
                snackbar.show(
                    response._data.message || "Failed to create user",
                    "error",
                )
            },
        })

        emit("getUser")

        snackbar.show(response.message, "success")

        closeDialog()
    } catch (e) {
        snackbar.show("Server error occurred", "error")
    }
}

const update = async () => {
    try {
        // TODO: Leave only ME request
        // let endpoint = $endpoint("UPDATE_USER", { id: formData.value.id })
        // if (props.me) endpoint = $endpoint("UPDATE_ME")

        const endpoint = $endpoint("UPDATE_ME")

        const response = await $api(endpoint, {
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
        })

        emit("getUser")

        snackbar.show(response.message)

        closeDialog()
    } catch (e) {
        console.log(e)
    }
}

const handleUpdateUserEmail = data => {
    formData.value.email = data
    emit("getUser")
    formKey.value++
}

const errors = ref({})
const formKey = ref(0)

const validate = () => {
    errors.value = {}

    const phoneNumberPattern = /^\+*\(?\d{1,3}\)?[-\s./0-9]*$/

    if (!formData.value.fname) errors.value.fname = ["Name is required"]
    if (!formData.value.sname) errors.value.sname = ["Surname is required"]
    if (!formData.value.email) errors.value.email = ["Email is required"]
    if (
        formData.value.email &&
        !/^\S[^\s@]*@\S[^\s.]*\.\S+$/.test(formData.value.email)
    )
        errors.value.email = ["Email must be a valid email address"]

    if (
        formData.value.phone &&
        !phoneNumberPattern.test(formData.value.phone)
    ) {
        errors.value.phone = ["Phone number is not valid"]
    } else if (formData.value.phone && formData.value.phone.length > 15) {
        errors.value.phone = ["Phone number can not be this long"]
    }

    formKey.value++

    return !Object.keys(errors.value).length
}

const closeDialog = () => {
    isDialogVisible.value = false
    formData.value = { ...emptyUser }
    errors.value = {}
    formKey.value++
}

const getUserInitials = (fname, sname) => {
    if (!fname) return ""
    
    return fname[0] + (sname ? sname[0] : "")
}

onMounted(() => {
    window.addEventListener("keydown", e => {
        if (e.key === "Escape") {
            closeDialog()
        }
    })
})

onBeforeUnmount(() => {
    window.removeEventListener("keydown", e => {
        if (e.key === "Escape") {
            closeDialog()
        }
    })
})
</script>
