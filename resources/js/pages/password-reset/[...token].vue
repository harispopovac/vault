<template>
    <ResetPassword />
</template>

<script setup>
import ResetPassword from "@/views/auth/ResetPassword.vue"

definePage({
    meta: {
        layout: "blank",
        auth: false,
        public: true,
    },
})
</script>
