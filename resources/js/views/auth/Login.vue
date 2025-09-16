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
                                Vault
                                <!--                                <VNodeRenderer -->
                                <!--                                    style="height: 50px;" -->
                                <!--                                    :nodes="$vuetify.theme.name === 'dark' ? themeConfig.app.logo : themeConfig.app.logoPurple" -->
                                <!--                                /> -->
                            </div>
                        </RouterLink>
                    </VCardTitle>
                </VCardItem>

                <VCardText>
                    <VForm @submit.prevent="() => {}">
                        <VRow>
                            <!-- email -->
                            <VCol cols="12">
                                <AppTextField
                                    :key="formKey"
                                    v-model="form.email"
                                    autofocus
                                    label="Email"
                                    type="email"
                                    placeholder="johndoe@email.com"
                                    :error-messages="errors.email"
                                    :error="errors.email"
                                />
                            </VCol>

                            <!-- password -->
                            <VCol cols="12">
                                <AppTextField
                                    :key="formKey"
                                    v-model="form.password"
                                    label="Password"
                                    placeholder="············"
                                    :type="
                                        isPasswordVisible ? 'text' : 'password'
                                    "
                                    :append-inner-icon="
                                        isPasswordVisible
                                            ? 'tabler-eye-off'
                                            : 'tabler-eye'
                                    "
                                    :error-messages="errors.password"
                                    :error="errors.password"
                                    @click:append-inner="
                                        isPasswordVisible = !isPasswordVisible
                                    "
                                />
                            </VCol>

                            <VCol cols="12">
                                <!-- login button -->
                                <VBtn block type="submit" @click="login">
                                    Login
                                </VBtn>
                            </VCol>

                            <VCol cols="12" class="d-flex align-center">
                                <VDivider />
                                <span class="mx-4 text-high-emphasis">or</span>
                                <VDivider />
                            </VCol>

                            <!-- auth providers -->
                            <VCol cols="12" class="text-center mb-4">
                                <AuthProvider label="Sign in with Google" />
                            </VCol>

                            <VCol cols="12">
                                <div class="d-flex justify-center">
                                    <a href="/forgot-password">
                                        Forgot Password?
                                    </a>
                                </div>
                            </VCol>

                            <!-- create account -->
                            <VCol cols="12" class="text-body-1 text-center">
                                <span class="d-inline-block">
                                    New on our platform?
                                </span>
                                <RouterLink
                                    class="text-primary ms-1 d-inline-block text-body-1"
                                    to="/register"
                                >
                                    Create an account
                                </RouterLink>
                            </VCol>
                        </VRow>
                    </VForm>
                </VCardText>

                <VCardText>
                    <div class="d-flex gap-x-6 text-sm justify-center">
                        <RouterLink to="/terms-of-service" class="text-primary">
                            Terms of Service
                        </RouterLink>
                        <RouterLink to="/privacy-policy" class="text-primary">
                            Privacy Policy
                        </RouterLink>
                        <RouterLink to="/refund-policy" class="text-primary">
                            Refund Policy
                        </RouterLink>
                    </div>
                </VCardText>
            </VCard>
        </div>
    </div>

    <VerifyDialog :token="tempToken" :form="form" />
</template>

<script setup>
import AuthProvider from "@/views/pages/authentication/AuthProvider.vue"
import authV1BottomShape from "@images/svg/auth-v1-bottom-shape.svg?raw"
import authV1TopShape from "@images/svg/auth-v1-top-shape.svg?raw"
import VerifyDialog from "./dialogs/VerifyDialog.vue"

import { VNodeRenderer } from "@layouts/components/VNodeRenderer"

import { useSnackbarStore } from "@/stores/snackbar"

const snackbar = useSnackbarStore()

const form = ref({
    email: "",
    password: "",
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
})

const isPasswordVisible = ref(false)

const userData = ref(null)

const router = useRouter()

const login = async () => {
    if (!validate()) return

    try {
        const response = await $api($endpoint("LOGIN"), {
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

        if (response.verification) {
            tempToken.value = response.token

            return (isInfoEmailDialogVisible.value = true)
        }

        if (response.accessToken) {
            useCookie("accessToken").value = response.accessToken
            useCookie("user").value = JSON.stringify(response.user)

            userData.value = response
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

const errors = ref({})
const formKey = ref(0)

const isInfoEmailDialogVisible = ref(false)

provide("isInfoEmailDialogVisible", isInfoEmailDialogVisible)

const tempToken = ref(null)

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

    formKey.value++

    return !Object.keys(errors.value).length
}

watch(
    () => form.value.email,
    (n, o) => {
        if (n !== o) {
            form.value.email = n.toLowerCase()
        }
    },
)
</script>

<style lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>
