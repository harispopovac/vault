<template>
    <VSnackbar
        v-model="snackbar.isSnackbarActive"
        location="top"
        :color="snackbar.snackbarColor"
        :timeout="snackbar.snackbarTimeout"
    >
        {{ snackbar.snackbarMessage }}
    </VSnackbar>
</template>

<script setup>
import { useSnackbarStore } from "@/stores/snackbar"

const snackbar = useSnackbarStore()
</script>
