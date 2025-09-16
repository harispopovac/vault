<template>
    <MyProfile />
</template>

<script setup>
import MyProfile from "@/views/my-profile/MyProfile.vue"

definePage({
    name: "MyProfile",
    meta: {
        auth: true,
        layout: "default",
    },
})
</script>
