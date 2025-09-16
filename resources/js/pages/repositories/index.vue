<template>
  <Repositories />
</template>

<script setup>
// Import the main Repositories component from views
import Repositories from '@/views/pages/repositories/Repositories.vue'

// Define page metadata using definePage
definePage({
  name: 'Repositories',
  meta: {
    auth: true,
    layout: 'default',
  },
})
</script>
