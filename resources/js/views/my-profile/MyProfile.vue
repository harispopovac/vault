<template>
    <VRow>
        <VCol cols="12" md="5" lg="4">
            <UserBioPanel
                me
                :user-data="userData"
                @get-user-data="getUserData"
            />
        </VCol>

        <VCol cols="12" md="7" lg="8">
            <VTabs v-model="userTab" class="v-tabs-pill">
                <VTab v-for="tab in tabs" :key="tab.icon">
                    <VIcon :size="18" :icon="tab.icon" class="me-1" />
                    <span>{{ tab.title }}</span>
                </VTab>
            </VTabs>

            <VWindow
                v-model="userTab"
                class="mt-6 disable-tab-transition"
                :touch="false"
            >
                <VWindowItem>
                    <VRow>
                        <VCol cols="12">
                            <PasswordTab @get-user-data="getUserData" />
                        </VCol>
                        <VCol cols="12">
                            <GoogleTab @get-user-data="getUserData" />
                        </VCol>
                    </VRow>
                </VWindowItem>
            </VWindow>
        </VCol>
    </VRow>
</template>

<script setup>
import { useAuthStore } from "@/stores/auth"
import UserBioPanel from "@/views/my-profile/UserBioPanel.vue"
import PasswordTab from "@/views/my-profile/tabs/PasswordTab.vue"
import GoogleTab from "@/views/my-profile/tabs/GoogleTab.vue"

const authStore = useAuthStore()

const userTab = ref(null)

const userData = ref({})

const tabs = [
    {
        icon: "tabler-key",
        title: "Security",
    },
]

onMounted(() => {
    getUserData()
})

onBeforeRouteUpdate((to, from, next) => {
    if (to.params.id !== from.params.id) {
        getUserData(to.params.id)
    }
    next()
})

import { useRoute } from "vue-router"

const route = useRoute()

const getUserData = async (to = null) => {
    try {
        userData.value = await $api(
            $endpoint("ME", {
                id: to ? to : route.params.id,
            }),
            {
                method: "GET",
            },
        )

        authStore.setUser(userData.value)

        if (userData.value.photo) {
            authStore.setUserAvatar(userData.value.photo)
        }
    } catch (e) {
        console.log(e)
    }
}
</script>
