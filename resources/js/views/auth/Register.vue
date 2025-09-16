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
                max-width="800"
                :class="$vuetify.display.smAndUp ? 'pa-6' : 'pa-0'"
            >
                <VCardItem class="justify-center">
                    <VCardTitle>
                        <RouterLink to="/">
                            <div class="app-logo mb-4">
                                template
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
                                    :error="Boolean(errors.email?.length)"
                                />
                            </VCol>

                            <!-- name -->
                            <VCol cols="12" sm="6">
                                <AppTextField
                                    :key="formKey"
                                    v-model="form.fname"
                                    autofocus
                                    label="Name"
                                    placeholder="John"
                                    :error-messages="errors.fname"
                                    :error="Boolean(errors.fname?.length)"
                                />
                            </VCol>

                            <!-- surname -->
                            <VCol cols="12" sm="6">
                                <AppTextField
                                    :key="formKey"
                                    v-model="form.sname"
                                    autofocus
                                    label="Surname"
                                    placeholder="Doe"
                                    :error-messages="errors.sname"
                                    :error="Boolean(errors.sname?.length)"
                                />
                            </VCol>

                            <!-- password -->
                            <VCol cols="12" sm="6">
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
                                    :error="Boolean(errors.password?.length)"
                                    @click:append-inner="
                                        isPasswordVisible = !isPasswordVisible
                                    "
                                />
                            </VCol>

                            <VCol cols="12" sm="6">
                                <AppTextField
                                    :key="formKey"
                                    v-model="form.password_confirmation"
                                    label="Repeat Password"
                                    placeholder="············"
                                    :type="
                                        isPasswordConfirmVisible
                                            ? 'text'
                                            : 'password'
                                    "
                                    :append-inner-icon="
                                        isPasswordConfirmVisible
                                            ? 'tabler-eye-off'
                                            : 'tabler-eye'
                                    "
                                    :error-messages="
                                        errors.password_confirmation
                                    "
                                    :error="
                                        Boolean(
                                            errors.password_confirmation
                                                ?.length,
                                        )
                                    "
                                    @click:append-inner="
                                        isPasswordConfirmVisible =
                                            !isPasswordConfirmVisible
                                    "
                                />
                            </VCol>

                            <VCol cols="12">
                                <div class="text-sm d-flex align-center justify-space-between pb-1"
                                >
                                    <div>
                                        Account Timezone<span v-if="!showTimezone"
                                        >: {{ formattedTimezone }}</span
                                        >
                                    </div>

                                    <div
                                        v-if="!showTimezone"
                                        class="text-primary d-inline-block cursor-pointer ms-1"
                                        @click="showTimezone = true"
                                    >
                                        Change
                                    </div>
                                </div>

                                <TimezoneSelector
                                    v-if="showTimezone"
                                    v-model="form.timezone"
                                    class="cursor-pointer"
                                    label=""
                                />
                            </VCol>

                            <VCol cols="12" class="text-md">
                                <div class="d-flex align-center">
                                    <VCheckbox
                                        v-model="form.marketing_consent"
                                        class="d-block"
                                    />

                                    I would like to subscribe to template's
                                    newsletter and receive product updates and
                                    offers.
                                </div>

                                <div class="d-flex align-center">
                                    <VCheckbox
                                        v-model="form.terms"
                                        class="d-block"
                                        :error="Boolean(errors.terms)"
                                    />

                                    <div
                                        :class="{
                                            'text-error': errors.terms?.length,
                                        }"
                                    >
                                        I agree to the template
                                        <a
                                            href="/terms-of-service"
                                            class="text-primary"
                                        >Terms of Service</a
                                        >
                                        and
                                        <a
                                            href="/privacy-policy"
                                            class="text-primary"
                                        >Privacy Policy</a
                                        >.
                                    </div>
                                </div>
                                <!--                                <div v-if="errors.terms" class="text-sm text-error ms-4"> -->
                                <!--                                    {{ errors.terms[0] }} -->
                                <!--                                </div> -->
                            </VCol>

                            <VCol cols="12">
                                <VBtn
                                    block
                                    type="submit"
                                    @click="checkRegister"
                                >
                                    Register
                                </VBtn>
                            </VCol>

                            <VCol cols="12" class="d-flex align-center">
                                <VDivider />
                                <span class="mx-4 text-high-emphasis">or</span>
                                <VDivider />
                            </VCol>

                            <!-- auth providers -->
                            <VCol cols="12" class="text-center mb-4">
                                <AuthProvider label="Sign up with Google" />
                            </VCol>

                            <VCol cols="12" class="text-body-1 text-center">
                                <span class="d-inline-block">
                                    Already have an account?
                                </span>
                                <RouterLink
                                    class="text-primary ms-1 d-inline-block text-body-1"
                                    to="/login"
                                >
                                    Login
                                </RouterLink>
                            </VCol>
                        </VRow>
                    </VForm>
                </VCardText>

                <VCardText>
                    <div class="d-flex gap-x-6 text-sm justify-center">
                        <RouterLink
                            to="/terms-of-service"
                            class="text-primary"
                            target="_blank"
                        >
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
import VerifyDialog from "./dialogs/VerifyDialog.vue"
import authV1BottomShape from "@images/svg/auth-v1-bottom-shape.svg?raw"
import authV1TopShape from "@images/svg/auth-v1-top-shape.svg?raw"
import { VNodeRenderer } from "@layouts/components/VNodeRenderer"
import { themeConfig } from "@themeConfig"

import AuthProvider from "@/views/pages/authentication/AuthProvider.vue"

import { useSnackbarStore } from "@/stores/snackbar"

const snackbar = useSnackbarStore()

const form = ref({
    email: "",
    password: "",
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
})

const isPasswordVisible = ref(false)
const isPasswordConfirmVisible = ref(false)
const showTimezone = ref(false)

const isInfoEmailDialogVisible = ref(false)

provide("isInfoEmailDialogVisible", isInfoEmailDialogVisible)

const tempToken = ref(null)

const checkRegister = () => {
    if (!validate()) return

    register()
}

const register = async () => {
    if (!validate()) return

    try {
        const response = await $api($endpoint("REGISTER"), {
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

        tempToken.value = response.token
        isInfoEmailDialogVisible.value = true
    } catch (e) {
        console.log(e)
    }
}

const errors = ref({})
const formKey = ref(0)

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
        errors.value.password_confirmation = ["Passwords do not match"]
    }

    if (!form.value.fname) {
        errors.value.fname = ["First name is required"]
    }

    if (!form.value.sname) {
        errors.value.sname = ["Surname is required"]
    }

    if (!form.value.terms) {
        errors.value.terms = [
            "You must agree to the Terms of Service and Privacy Policy",
        ]
    }

    formKey.value++

    return !Object.keys(errors.value).length
}

const formattedTimezone = computed(() => {
    return form.value.timezone
        .replace(/_/g, " ")
        .replace(/\//g, " / ")
        .replace(/(\/\w)/g, m => m.toUpperCase())
})

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
