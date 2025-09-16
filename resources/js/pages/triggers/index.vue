<template>
  <Triggers />
</template>

<script setup>
// Import the main Triggers component from views
import Triggers from '@/views/pages/triggers/Triggers.vue'

// Define page metadata using definePage
definePage({
  name: 'Triggers',
  meta: {
    auth: true,
    layout: 'default',
  },
})
</script>
