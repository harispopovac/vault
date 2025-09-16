<template>
    <ForgotPassword />
</template>

<script setup>
import ForgotPassword from "@/views/auth/ForgotPassword.vue"

definePage({
    name: "ForgotPassword",
    meta: {
        layout: "blank",
        auth: false,
        public: true,
    },
})
</script>
