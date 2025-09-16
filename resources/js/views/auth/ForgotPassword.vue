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
                            <div v-if="success" class="sucess-msg">
                                Password reset link is sent to your email.
                            </div>
                            <!-- email -->
                            <VCol cols="12">
                                <AppTextField
                                    :key="formKey"
                                    v-model="form.email"
                                    autofocus
                                    label="Enter your Email"
                                    type="email"
                                    placeholder="johndoe@email.com"
                                    color="#2f528c"
                                    :error="Boolean(errors.email?.length)"
                                    :error-messages="errors.email"
                                />
                            </VCol>

                            <!-- login button -->
                            <VCol cols="12">
                                <VBtn
                                    block
                                    type="submit"
                                    :disabled="isSending || isLinkSent"
                                    @click="requestReset"
                                >
                                    Reset Password
                                </VBtn>
                            </VCol>

                            <VCol cols="12" class="pt-0">
                                <a href="/login"> Back to Log In </a>
                            </VCol>
                        </VRow>
                    </VForm>
                </VCardText>
            </VCard>
        </div>
    </div>
</template>

<script setup>
import authV1BottomShape from "@images/svg/auth-v1-bottom-shape.svg?raw"
import authV1TopShape from "@images/svg/auth-v1-top-shape.svg?raw"
import { VNodeRenderer } from "@layouts/components/VNodeRenderer"
import { themeConfig } from "@themeConfig"

import { useSnackbarStore } from "@/stores/snackbar"

const snackbar = useSnackbarStore()

const success = ref(false)
const errors = ref({})
const formKey = ref(0)

const form = ref({
    email: "",
})

const isSending = ref(false)
const isLinkSent = ref(false)

const requestReset = async () => {
    if (!validate()) return
    isSending.value = true

    try {
        const response = await $api($endpoint("REQUEST_RESET_PASSWORD"), {
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

        isLinkSent.value = true

        if (response) {
            success.value = true
        }
    } catch (e) {
        console.log(e)
    } finally {
        isSending.value = false
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

.sucess-msg {
    margin: 1rem auto;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.375rem;
    background-color: rgba(16, 185, 129, 0.1);
    padding: 0.5rem 1rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.2);
}
</style>
