import { setupLayouts } from "virtual:generated-layouts"
import { createRouter, createWebHistory } from "vue-router/auto"

function recursiveLayouts(route) {
    if (route.children) {
        for (let i = 0; i < route.children.length; i++)
            route.children[i] = recursiveLayouts(route.children[i])

        return route
    }

    return setupLayouts([route])[0]
}

const router = createRouter({
    history: createWebHistory(import.meta.env.APP_BASE_URL), // This fixes /build in url
    scrollBehavior(to) {
        if (to.hash) return { el: to.hash, behavior: "smooth", top: 60 }

        return { top: 0 }
    },
    extendRoutes: pages => [
        ...[...pages].map(route => recursiveLayouts(route)),
    ],
})

router.beforeEach((to, from, next) => {
    const accessToken = useCookie("accessToken").value

    if (
        !window.location.hostname.startsWith(import.meta.env.VITE_APP_SUBDOMAIN)
    ) {
        if (
            to.path === "/privacy-policy" ||
            to.path === "/terms-of-service" ||
            to.path === "/refund-policy"
        ) {
            return next()
        }

        if (!(to.path === "/")) {
            return next("/")
        } else {
            next()
        }
    } else {
        if (to.meta.auth) {
            if (!accessToken) {
                return next("/login")
            }

            if (to.meta.access) {
                return next("/")
            }

            return next()
        } else {
            if (to.meta.public) {
                return next()
            }

            if (accessToken) {
                return next("/")
            }
        }

        return next()
    }
})

export { router }
export default function (app) {
    app.use(router)
}
