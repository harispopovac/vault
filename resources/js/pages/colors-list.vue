<script setup>
import { ColorsTable } from "colors-module-frontend"

definePage({
    name: "ColorsList",
    meta: {
        auth: true,
        layout: "default",
        title: "Colors List",
        description: "View and manage colors",
    },
})
</script>

<template>
    <ColorsTable />
</template>
