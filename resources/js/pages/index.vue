<template>
    <Dashboard />
</template>

<script setup>
import Dashboard from "@/views/dashboard/Dashboard.vue"

definePage({
    name: "Dashboard",
    meta: {
        auth: true,
        layout: "default",
        public: false,
    },
})
</script>
