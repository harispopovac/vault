<script setup>
import { AddressList } from "address-module-frontend"

definePage({
    name: "Addresses",
    meta: {
        auth: true,
        layout: "default",
        title: "Addresses",
        description: "Manage and test address CRUD",
    },
})
</script>

<template>
    <AddressList />
</template>
