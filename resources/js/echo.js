import Echo from "laravel-echo"
import Pusher from "pusher-js"

window.Pusher = Pusher

window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
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
