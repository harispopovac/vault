import { ofetch } from "ofetch"

export const $api = ofetch.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || "/api/v1",
    credentials: "include", // Add this line to include cookies with each request
    async onRequest({ options }) {
        const accessToken = useCookie("accessToken").value
        if (accessToken) {
            options.headers = {
                ...options.headers,
                Accept: "application/json",
                Authorization: `Bearer ${accessToken}`,
            }
        }
    },
    async onResponse({ response }) {
        if (response.status === 401) {
            useCookie("accessToken").value = null
            useCookie("user").value = null
            localStorage.removeItem("generalData")
            localStorage.removeItem("weeklyOverviewData")

            await window.router.push("/login")
        }
        if (response.status === 403) {
            await window.router.push("/")
        }
        if (response.status === 404) {
            await window.router.push("/404")
        }
    },
})
