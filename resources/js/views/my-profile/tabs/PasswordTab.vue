<template>
    <VRow>
        <VCol cols="12">
            <VCard
                :title="
                    auth.user?.isPassword ? 'Change Password' : 'Set Password'
                "
            >
                <VCardText>
                    <VRow>
                        <VCol v-if="auth.user?.isPassword" cols="12">
                            <AppTextField
                                v-model="old_password"
                                autofocus
                                label="Current Password"
                                type="password"
                                placeholder="············"
                                :error="Boolean(errors?.old_password?.length)"
                                :error-messages="errors?.old_password"
                            />
                        </VCol>
                        <VCol cols="12" md="6">
                            <AppTextField
                                v-model="password"
                                autofocus
                                :label="
                                    auth.user?.isPassword
                                        ? 'New Password'
                                        : 'Set Password'
                                "
                                type="password"
                                placeholder="············"
                                :error="Boolean(errors?.password?.length)"
                                :error-messages="errors?.password"
                            />
                        </VCol>

                        <VCol cols="12" md="6">
                            <AppTextField
                                v-model="password_confirmation"
                                label="Password Confirmation"
                                placeholder="············"
                                type="password"
                                class="rounded-none mb-2"
                                :error="
                                    Boolean(
                                        errors?.password_confirmation?.length,
                                    )
                                "
                                :error-messages="errors?.password_confirmation"
                            />
                        </VCol>
                        <VCol cols="12" md="4" class="ml-auto">
                            <VBtn block type="submit" @click="changePassword">
                                {{
                                    auth.user?.isPassword
                                        ? "Change Password"
                                        : "Set Password"
                                }}
                            </VBtn>
                        </VCol>
                    </VRow>
                </VCardText>
            </VCard>
        </VCol>
    </VRow>
</template>

<script setup>
import { useAuthStore } from "@/stores/auth"
import { useSnackbarStore } from "@/stores/snackbar"

const emit = defineEmits(["getUserData"])

const auth = useAuthStore()

const old_password = ref("")
const password = ref("")
const password_confirmation = ref("")
const errors = ref({})

const snackbar = useSnackbarStore()

const changePassword = async () => {
    errors.value = {}
    try {
        await $api($endpoint("CHANGE_PASSWORD"), {
            body: {
                old_password: old_password.value,
                password: password.value,
                password_confirmation: password_confirmation.value,
            },
            method: "PUT",
        })

        password.value = ""
        password_confirmation.value = ""
        old_password.value = ""

        snackbar.show("Password changed successfully")

        emit("getUserData")
    } catch (e) {
        errors.value = e.response._data.errors
        snackbar.show(e.response._data.message || "An error occurred", "error")
    }
}
</script>
