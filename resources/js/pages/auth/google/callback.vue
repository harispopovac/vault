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

                <VCardText class="d-flex justify-center">
                    <div class="loader"></div>
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

definePage({
    name: "AuthGoogleCallback",
    meta: {
        layout: "blank",
        auth: false,
        public: true,
    },
})

import { useRouter } from "vue-router"

const router = useRouter()

onMounted(() => {
    handleGoogleCallback()
})

const handleGoogleCallback = async () => {
    const urlParams = new URLSearchParams(window.location.search)
    const encodedData = urlParams.get("data")

    if (encodedData) {
        const payload = JSON.parse(decodeURIComponent(encodedData))
        const { accessToken, user } = payload

        if (accessToken) {
            useCookie("accessToken").value = accessToken
            useCookie("user").value = JSON.stringify(user)

            await nextTick(async () => {
                try {
                    const response = await $api(
                        $endpoint("SET_CURRENT_TIMEZONE"),
                        {
                            method: "PUT",
                            body: {
                                timezone:
                                    Intl.DateTimeFormat().resolvedOptions()
                                        .timeZone,
                            },
                        },
                    )

                    useCookie("user").value = JSON.stringify(response.user)

                    await router.push("/")
                } catch (err) {
                    await router.push("/")
                }
            })
        } else {
            await router.push("/login")
        }
    } else {
        await router.push("/login")
    }
}
</script>

<style lang="scss">
@use "@core-scss/template/pages/page-auth.scss";

.v-theme--dark {
    .loader {
        color: white;
    }
}

.v-theme--light {
    .loader {
        color: #7367f0;
    }
}

/* HTML: <div class="loader"></div> */
.loader {
    width: 60px;
    aspect-ratio: 1;
    display: flex;
    border: 4px solid;
    box-sizing: border-box;
    border-radius: 50%;
    background:
        radial-gradient(circle 5px, currentColor 100%, #0000),
        linear-gradient(currentColor 50%, #0000 0) 50%/4px 60% no-repeat;
    animation: l1 2s infinite linear;
}

.loader:before {
    content: "";
    flex: 1;
    background: linear-gradient(currentColor 50%, #0000 0) 50%/4px 80% no-repeat;
    animation: inherit;
}

@keyframes l1 {
    100% {
        transform: rotate(1turn);
    }
}
</style>
