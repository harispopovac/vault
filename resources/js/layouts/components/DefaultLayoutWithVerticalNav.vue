<template>
    <div
        v-if="isLoading"
        class="d-flex justify-center align-center"
        style="height: 100vh"
    >
        <VProgressCircular color="primary" indeterminate size="64" />
    </div>
    <VerticalNavLayout v-else :nav-items="navItems">
        <!-- 👉 navbar -->
        <template #navbar="{ toggleVerticalOverlayNavActive }">
            <div class="d-flex h-100 align-center gap">
                <IconBtn
                    id="vertical-nav-toggle-btn"
                    class="ms-n3 d-lg-none"
                    @click="toggleVerticalOverlayNavActive(true)"
                >
                    <VIcon size="26" icon="tabler-menu-2" />
                </IconBtn>

                <VSpacer />

                <NavBarI18n
                    v-if="
                        themeConfig.app.i18n.enable &&
                            themeConfig.app.i18n.langConfig?.length
                    "
                    :languages="themeConfig.app.i18n.langConfig"
                />
                <UserProfile />
            </div>
        </template>

        <!-- 👉 Pages -->
        <slot />

        <!-- 👉 Footer -->
        <template #footer>
            <Footer />
        </template>

        <!-- 👉 Customizer -->
        <!-- <TheCustomizer /> -->
    </VerticalNavLayout>
</template>

<script setup>
import navItems from "@/navigation/vertical"
import { useAuthStore } from "@/stores/auth"
import { themeConfig } from "@themeConfig"

// Components
import Footer from "@/layouts/components/Footer.vue"
import UserProfile from "@/layouts/components/UserProfile.vue"
import NavBarI18n from "@core/components/I18n.vue"

// @layouts plugin
import { VerticalNavLayout } from "@layouts"

const user = ref({})
const localTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone
const isLoading = ref(true)

import { useRouter } from "vue-router"

provide("localTimezone", localTimezone)

const timezones = ref([])

provide("timezones", timezones)

// Global debug function for staging
window.clearAuthCookies = () => {
    useCookie("accessToken").value = null
    useCookie("user").value = null
    useCookie("abilities").value = null
    console.log("Auth cookies cleared. Please refresh the page.")
}

onMounted(async () => {
    // Set initial user data from cookie if available
    const userData = getSafeUserFromCookie()
    if (userData && userData.id) {
        useAuthStore().setUser(userData)
        user.value = userData
        if (userData.photo) {
            useAuthStore().setUserAvatar(userData.photo)
        }
        console.log("Loaded user from cache:", userData.email || userData.id)
    }

    // Set loading to false after a short delay to ensure layout renders
    setTimeout(() => {
        isLoading.value = false
    }, 100)

    // Then try to refresh user data from API
    await me()
})

const me = async () => {
    try {
        // Check if we have an access token
        const accessToken = useCookie("accessToken").value

        if (!accessToken) {
            console.log("No access token found, redirecting to login")
            await router.push("/login")
            
            return
        }

        console.log("Fetching user data from /me endpoint...")

        const response = await $api($endpoint("ME"), {
            method: "GET",
            onResponseError({ response }) {
                console.error("ME API Error:", response.status, response._data)

                if (response.status === 401) {
                    console.log(
                        "Unauthorized - clearing tokens and redirecting to login",
                    )
                    logout()
                }
            },
        })

        console.log("User data received:", response)

        if (response.logout) {
            return await logout()
        }

        // user.value = response
        useCookie("user").value = JSON.stringify(response)
        user.value = response

        if (response.photo) {
            useAuthStore().setUserAvatar(response.photo)
        }

        useAuthStore().setUser(response)
        console.log("User data updated successfully")
    } catch (err) {
        console.error("Error in me() function:", err)


        // Don't redirect on error - just log it and continue with cached user data
        const cachedUser = getSafeUserFromCookie()
        if (cachedUser && cachedUser.id) {
            console.log("Using cached user data after API error")
            useAuthStore().setUser(cachedUser)
            user.value = cachedUser
        }
    }
}

const router = useRouter()

// Helper function to safely parse user data from cookies
const getSafeUserFromCookie = () => {
    try {
        const cachedUser = useCookie("user").value
        if (!cachedUser) return null

        if (typeof cachedUser === "object") {
            return cachedUser
        } else if (typeof cachedUser === "string") {
            if (
                cachedUser === "[object Object]" ||
                cachedUser === "undefined" ||
                cachedUser === "null"
            ) {
                console.warn("Invalid cookie data detected, clearing cookie")
                useCookie("user").value = null
                
                return null
            }
            
            return JSON.parse(cachedUser)
        }
        
        return null
    } catch (e) {
        console.error("Error parsing user cookie:", e)
        console.log("Clearing corrupted cookie")
        useCookie("user").value = null
        
        return null
    }
}

const logout = async () => {
    try {
        await $api($endpoint("LOGOUT"), {
            method: "POST",
            onResponseError({ response }) {
                if (response._data.message) {
                    // snackbar.show(response._data.message, "error")
                }
            },
        })

        useCookie("accessToken").value = null
        useCookie("user").value = null
        useCookie("abilities").value = null

        await router.push("/login")
    } catch (e) {
        console.log(e)
    }
}
</script>
