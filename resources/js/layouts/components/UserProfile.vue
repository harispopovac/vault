<template>
    <VAvatar class="cursor-pointer" color="primary" variant="tonal">
        <VImg v-if="userAvatar" :src="userAvatar" />
        <span v-else class="text-md">{{
            avatarText(`${user?.fname} ${user?.sname}`)
        }}</span>

        <!-- SECTION Menu -->
        <VMenu
            v-model="menuVisible"
            activator="parent"
            location="bottom end"
            offset="14px"
            width="300"
            :close-on-content-click="false"
        >
            <VList>
                <!-- 👉 User Avatar & Name -->
                <VListItem>
                    <template #prepend>
                        <VListItemAction start>
                            <VAvatar color="primary" variant="tonal">
                                <VImg v-if="userAvatar" :src="userAvatar" />
                                <span v-else class="text-md">{{
                                    getInitials(user?.fullName)
                                }}</span>
                            </VAvatar>
                        </VListItemAction>
                    </template>

                    <VListItemTitle class="font-weight-semibold">
                        {{ user?.fname }} {{ user?.sname }}
                    </VListItemTitle>
                    <VListItemSubtitle>{{ user?.email }}</VListItemSubtitle>
                </VListItem>

                <VListItem class="pa-0 ma-0">
                    <div class="text-sm mb-1">Quick Settings</div>
                    <div class="d-flex gap-1">
                        <div>
                            <NavbarThemeSwitcher />
                        </div>
                    </div>
                </VListItem>

                <VDivider class="my-2" />

                <!-- 👉 Profile -->
                <VListItem @click="router.push(`/my-profile`)">
                    <template #prepend>
                        <VIcon class="me-2" icon="tabler-user" size="22" />
                    </template>

                    <VListItemTitle>Profile</VListItemTitle>
                </VListItem>

                <!-- Divider -->
                <VDivider class="my-2" />

                <!-- 👉 Logout -->
                <VListItem @click="logout">
                    <template #prepend>
                        <VIcon class="me-2" icon="tabler-logout" size="22" />
                    </template>

                    <VListItemTitle>Logout</VListItemTitle>
                </VListItem>
            </VList>
        </VMenu>
        <!-- !SECTION -->
    </VAvatar>
</template>

<script setup>
import NavbarThemeSwitcher from "@/layouts/components/NavbarThemeSwitcher.vue"
import { useAuthStore } from "@/stores/auth"
import { useSnackbarStore } from "@/stores/snackbar"
import Echo from "laravel-echo"
import Pusher from "pusher-js"

const snackbar = useSnackbarStore()
const authStore = useAuthStore()

const user = ref(useCookie("user").value)
const userAvatar = computed(() => authStore.userAvatar)
const menuVisible = ref(false)

const router = useRouter()

watch(router.currentRoute, (to, from) => {
    if (to.path !== from.path) {
        menuVisible.value = false
    }
})

onMounted(() => {
    if (user.value) {
        window.Pusher = Pusher

        window.Echo = new Echo({
            broadcaster: "reverb",
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS:
                (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
            cluster: import.meta.env.VITE_REVERB_CLUSTER ?? "eu",
            enabledTransports: ["ws", "wss"],
            disableStats: true,
            authEndpoint: "/api/broadcasting/auth",
            auth: {
                headers: {
                    Authorization: `Bearer ${useCookie("accessToken").value}`,
                    "X-XSRF-TOKEN": useCookie("XSRF-TOKEN").value,
                },
            },
        })
    }
})

const getInitials = fullName => {
    if (!fullName) return ""
    const [firstName = "", lastName = ""] = fullName.split(" ")

    return firstName.charAt(0) + lastName.charAt(0)
}

const logout = async () => {
    try {
        await $api($endpoint("LOGOUT"), {
            method: "POST",
            onResponseError({ response }) {
                if (response._data.message) {
                    snackbar.show(response._data.message, "error")
                }
            },
        })

        useCookie("accessToken").value = null
        useCookie("user").value = null

        await location.reload()
    } catch (e) {
        console.log(e)
    }
}
</script>
