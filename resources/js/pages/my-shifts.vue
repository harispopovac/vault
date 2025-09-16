<script setup>
import { MyShifts } from "shift-log-module-frontend"

definePage({
    name: "MyShifts",
    meta: {
        auth: true,
        layout: "default",
        title: "My Shifts",
        description: "View and manage your shift logs",
    },
})
</script>

<template>
    <MyShifts />
</template>
