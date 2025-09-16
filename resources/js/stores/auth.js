export const useAuthStore = defineStore("auth", () => {
    const userAvatar = ref(null)
    const user = ref({})

    function setUserAvatar(avatar) {
        userAvatar.value = avatar
    }

    function setUser(userData) {
        user.value = userData
        useCookie("user").value = JSON.stringify(userData)
    }

    return {
        userAvatar,
        setUserAvatar,
        user,
        setUser,
    }
})
