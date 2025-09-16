<script setup>
import { ShiftLog } from "shift-log-module-frontend"

definePage({
    name: "ShiftLog",
    meta: {
        auth: true,
        layout: "default",
        title: "Shift Log",
        description: "Manage and view shift logs for all staff members",
    },
})
</script>

<template>
    <ShiftLog />
</template>
