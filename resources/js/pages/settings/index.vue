<template>
  <Settings />
</template>

<script setup>
import Settings from '@/views/pages/settings/Settings.vue'

// Define page metadata using definePage
definePage({
  name: 'Settings',
  meta: {
    auth: true,
    layout: 'default',
  },
})
</script>