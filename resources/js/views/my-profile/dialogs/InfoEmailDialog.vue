<template>
    <VDialog
        :model-value="Boolean(isInfoEmailDialogVisible)"
        width="512"
        persistent
        no-click-animation
        @update:model-value="onReset"
    >
        <!-- 👉 Dialog close btn -->
        <DialogCloseBtn @click="onReset" />

        <VCard
            :title="isEmailSent ? 'Verify New Email' : 'Change Email'"
            class="pb-4"
        >
            <VCardText>
                <VRow>
                    <VCol v-if="!isEmailSent" cols="12">
                        <AppTextField
                            v-model="userData.email"
                            :error="Boolean(errors.email?.length)"
                            :error-messages="errors.email"
                            placeholder="Enter New Email"
                        />
                    </VCol>
                    <VCol v-else cols="12">
                        <p class="text-sm">
                            We have sent an email to
                            <strong>{{ userData.email }}</strong>
                            with a 6-digit one-time-password. Please enter the
                            OTP below.
                        </p>
                        <VOtpInput
                            v-model="otp"
                            class="mb-2"
                            autofocus
                            :error="error"
                        />
                        <p class="text-sm">The OTP will expire in 3 minutes.</p>
                    </VCol>
                </VRow>
            </VCardText>

            <!-- 👉 Actions button -->
            <VFooter class="d-flex align-center gap-4 px-6">
                <VBtn
                    class="flex-grow-0"
                    color="secondary"
                    variant="tonal"
                    @click="onReset"
                >
                    Close
                </VBtn>
                <VBtn
                    v-if="!isEmailSent"
                    class="flex-grow-1"
                    color="primary"
                    @click="sendEmail"
                >
                    Submit
                </VBtn>
                <VBtn
                    v-else
                    class="flex-grow-1"
                    color="primary"
                    :disabled="!!timer"
                    @click="sendEmail"
                >
                    <span v-if="timer">Resend in {{ timer }}s</span>
                    <span v-else>Resend</span>
                </VBtn>
            </VFooter>
        </VCard>
    </VDialog>
</template>

<script setup>
const emit = defineEmits(["updateUser", "updateUserEmail"])

const isInfoEmailDialogVisible = inject("isInfoEmailDialogVisible", ref(false))

const userData = ref({
    email: "",
})

const isEmailSent = ref(false)
const otp = ref("")

const errors = ref([])

const timer = ref(0)

const interval = ref(null)

const sendEmail = async () => {
    if (!validate()) return

    const response = await $api($endpoint("ENROLL_USER_INFO"), {
        method: "POST",
        body: userData.value,
        onResponseError({ response }) {
            if (response._data.errors) {
                errors.value = response._data.errors
            }

            // if (response._data.message) {
            //     snackbar.show(response._data.message, "error")
            // }
        },
    })

    if (response.sent) {
        isEmailSent.value = true
        timer.value = 30

        interval.value = setInterval(() => {
            timer.value--

            if (timer.value <= 0) clearInterval(interval.value)
        }, 1000)
    }
}

const formKey = ref(0)

const validate = () => {
    errors.value = []

    if (!userData.value.email) {
        errors.value.email = ["Email is required"]
    }

    if (
        userData.value.email &&
        !/^\S[^\s@]*@\S[^\s.]*\.\S+$/.test(userData.value.email)
    ) {
        errors.value.email = ["Email must be a valid email address"]
    }

    formKey.value++

    return !Object.keys(errors.value).length
}

const error = ref(false)

const submitOtp = async () => {
    error.value = false

    const response = await $api($endpoint("VERIFY_USER_INFO"), {
        method: "POST",
        body: {
            otp: otp.value,
        },
    })

    if (response.verified) {
        onReset()
        emit("updateUserEmail", response.me?.email)
    } else {
        error.value = true
    }
}

watch(otp, newValue => {
    if (newValue.length === 6) {
        submitOtp()
    }
})

const onReset = () => {
    userData.value = {
        email: "",
    }
    isInfoEmailDialogVisible.value = false
    otp.value = ""
    isEmailSent.value = false
}
</script>
