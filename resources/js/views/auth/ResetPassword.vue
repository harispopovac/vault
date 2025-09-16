<template>
    <div class="auth-wrapper d-flex align-center justify-center pa-4">
        <div class="position-relative my-sm-16">
            <!-- 👉 Top shape -->
            <VNodeRenderer
                :nodes="h('div', { innerHTML: authV1TopShape })"
                class="text-primary auth-v1-top-shape d-none d-sm-block"
            />

            <!-- 👉 Bottom shape -->
            <VNodeRenderer
                :nodes="h('div', { innerHTML: authV1BottomShape })"
                class="text-primary auth-v1-bottom-shape d-none d-sm-block"
            />

            <!-- 👉 Auth Card -->
            <VCard
                class="auth-card"
                max-width="460"
                :class="$vuetify.display.smAndUp ? 'pa-6' : 'pa-0'"
            >
                <VCardItem class="justify-center">
                    <VCardTitle>
                        <RouterLink to="/">
                            <div class="app-logo mb-4">
                                <VNodeRenderer
                                    style="height: 50px"
                                    :nodes="
                                        $vuetify.theme.name === 'dark'
                                            ? themeConfig.app.logo
                                            : themeConfig.app.logoPurple
                                    "
                                />
                            </div>
                        </RouterLink>
                    </VCardTitle>
                </VCardItem>

                <VCardText>
                    <VForm @submit.prevent="() => {}">
                        <VRow>
                            <!-- email -->
                            <VCol cols="12" style="display: none">
                                <AppTextField
                                    :key="formKey"
                                    v-model="form.email"
                                    autofocus
                                    label="Email"
                                    type="email"
                                    placeholder="johndoe@email.com"
                                    color="#2f528c"
                                    :error="Boolean(errors.email?.length)"
                                    :error-messages="errors.email"
                                />
                            </VCol>
                            <!-- password -->
                            <VCol cols="12">
                                <div class="password-container position-relative d-flex align-items-center"
                                >
                                    <AppTextField
                                        :key="formKey"
                                        v-model="form.password"
                                        label="New Password"
                                        placeholder="············"
                                        color="#2f528c"
                                        :type="
                                            isPasswordVisible
                                                ? 'text'
                                                : 'password'
                                        "
                                        :error="
                                            Boolean(errors.password?.length)
                                        "
                                        :error-messages="errors.password"
                                    />
                                    <VIcon
                                        :icon="
                                            isPasswordVisible
                                                ? 'tabler-eye-off'
                                                : 'tabler-eye'
                                        "
                                        class="password-visibility-icon"
                                        @click="
                                            isPasswordVisible =
                                                !isPasswordVisible
                                        "
                                    />
                                </div>
                            </VCol>
                            <!-- password -->
                            <VCol cols="12">
                                <div class="password-container position-relative d-flex align-items-center"
                                >
                                    <AppTextField
                                        :key="formKey"
                                        v-model="form.password_confirmation"
                                        label="Confirm Password"
                                        placeholder="············"
                                        color="#2f528c"
                                        :type="
                                            isPassword2Visible
                                                ? 'text'
                                                : 'password'
                                        "
                                        :error="
                                            Boolean(
                                                errors.password_confirmation
                                                    ?.length,
                                            )
                                        "
                                        :error-messages="
                                            errors.password_confirmation
                                        "
                                    />
                                    <VIcon
                                        :icon="
                                            isPassword2Visible
                                                ? 'tabler-eye-off'
                                                : 'tabler-eye'
                                        "
                                        class="password-visibility-icon"
                                        @click="
                                            isPassword2Visible =
                                                !isPassword2Visible
                                        "
                                    />
                                </div>
                            </VCol>
                            <VCol cols="12" class="mt-2">
                                <!-- login button -->
                                <VBtn
                                    block
                                    type="submit"
                                    @click="resetPassword"
                                >
                                    Create New Password
                                </VBtn>
                            </VCol>
                        </VRow>
                    </VForm>
                </VCardText>
            </VCard>
        </div>
    </div>
</template>

<script setup>
import { useSnackbarStore } from "@/stores/snackbar"
import authV1BottomShape from "@images/svg/auth-v1-bottom-shape.svg?raw"
import authV1TopShape from "@images/svg/auth-v1-top-shape.svg?raw"
import { VNodeRenderer } from "@layouts/components/VNodeRenderer"
import { themeConfig } from "@themeConfig"

const snackbar = useSnackbarStore()

const errors = ref({})
const formKey = ref(0)
const isPasswordVisible = ref(false)
const isPassword2Visible = ref(false)

const router = useRouter()

const userData = ref(null)

const form = ref({
    email: route.query.email,
    token: route.params.token,
    password: "",
    password_confirmation: "",
})

const resetPassword = async () => {
    if (!validate()) return

    try {
        const response = await $api($endpoint("RESET_PASSWORD"), {
            method: "POST",
            body: form.value,
            onResponseError({ response }) {
                if (response._data.errors) {
                    errors.value = response._data.errors
                }

                if (response._data.message) {
                    snackbar.show(response._data.message, "error")
                }
            },
        })

        if (response.accessToken) {
            useCookie("accessToken").value = response.accessToken
            useCookie("user").value = JSON.stringify(response.user)

            userData.value = response
        }

        router.push("/")
    } catch (e) {
        console.log(e)
    }
}

const validate = () => {
    errors.value = {}

    if (!form.value.email) {
        errors.value.email = ["Email is required"]
    }

    if (
        form.value.email &&
        !/^\S[^\s@]*@\S[^\s.]*\.\S+$/.test(form.value.email)
    ) {
        errors.value.email = ["Email must be a valid email address"]
    }

    if (!form.value.password) {
        errors.value.password = ["Password is required"]
    }

    if (form.value.password && form.value.password.length < 8) {
        errors.value.password = ["Password must be at least 8 characters"]
    }

    if (!form.value.password_confirmation) {
        errors.value.password_confirmation = [
            "Password confirmation is required",
        ]
    }

    if (form.value.password_confirmation !== form.value.password) {
        errors.value.password_confirmation = [
            "Confirmed Password must be same as the first one.",
        ]
    }

    formKey.value++

    return !Object.keys(errors.value).length
}
</script>

<style lang="scss">
@use "@core-scss/template/pages/page-auth.scss";

.password-container {
    position: relative;
    flex-grow: 1;
}

.password-visibility-icon {
    position: absolute;
    right: 10px;
    cursor: pointer;
    margin-top: 32px;
}
</style>
