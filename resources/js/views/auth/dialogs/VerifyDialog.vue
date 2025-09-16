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

        <VCard title="Verify Email" class="pb-4">
            <VCardText>
                <VRow>
                    <VCol cols="12">
                        <p class="text-sm">
                            In order to continue using Vault you need to
                            verify email address. <br />
                            We have sent an email to
                            <strong>{{ form.email }}</strong>
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
                    class="flex-grow-1"
                    color="primary"
                    :disabled="!!timer"
                    @click="login"
                >
                    <span v-if="timer">Resend in {{ timer }}s</span>
                    <span v-else>Resend</span>
                </VBtn>
            </VFooter>
        </VCard>
    </VDialog>
</template>

<script setup>
const props = defineProps({
    token: {
        type: String,
        required: true,
    },
    form: {
        type: Object,
        required: true,
    },
})

const isInfoEmailDialogVisible = inject("isInfoEmailDialogVisible")
const otp = ref("")
const error = ref(false)
const errors = ref({})

const router = useRouter()

const onReset = () => {
    isInfoEmailDialogVisible.value = false
}

watch(isInfoEmailDialogVisible, n => {
    if (n) {
        timer.value = 30

        interval.value = setInterval(() => {
            timer.value--

            if (timer.value === 0) clearInterval(interval.value)
        }, 1000)
    }
})

const timer = ref(0)

const interval = ref(null)

const login = async () => {
    try {
        const response = await $api($endpoint("LOGIN"), {
            method: "POST",
            body: props.form,
            onResponseError({ response }) {
                if (response._data.errors) {
                    errors.value = response._data.errors
                }

                // if (response._data.message) {
                //     snackbar.show(response._data.message, "error")
                // }
            },
        })

        if (response.verification) {
            timer.value = 30

            return (interval.value = setInterval(() => {
                timer.value--

                if (timer.value <= 0) clearInterval(interval.value)
            }, 1000))
        }

        if (response.accessToken) {
            useCookie("accessToken").value = response.accessToken
            useCookie("user").value = JSON.stringify(response.user)
        }

        await fetch(
            `${import.meta.env.VITE_CSRF_COOKIE_URL}/sanctum/csrf-cookie`,
            {
                credentials: "include",
            },
        )
        router.push("/")
    } catch (e) {
        console.log(e)
    }
}

const submitOtp = async () => {
    error.value = false

    const response = await $api($endpoint("VERIFY_USER"), {
        method: "POST",
        body: {
            otp: otp.value,
            token: props.token,
        },
    })

    if (response.verified) {
        onReset()
        await login()
    } else {
        error.value = true
    }
}

watch(otp, newValue => {
    if (newValue?.length === 6) {
        submitOtp()
    }
})
</script>
