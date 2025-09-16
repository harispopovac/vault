import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useSnackbarStore = defineStore("snackbar", () => {
    const isSnackbarActive = ref(false)
    const snackbarMessage = ref("")
    const snackbarColor = ref("success")
    const snackbarTimeout = ref(3000)

    function show(message, color = "success", timeout = 3000) {
        snackbarMessage.value = message
        snackbarColor.value = color
        snackbarTimeout.value = timeout

        isSnackbarActive.value = true
    }

    return {
        isSnackbarActive,
        snackbarMessage,
        snackbarColor,
        snackbarTimeout,
        show,
    }
})
