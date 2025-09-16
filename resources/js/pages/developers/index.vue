<template>
  <Developers />
</template>

<script setup>
// Import the main Developers component from views
import Developers from '@/views/pages/developers/Developers.vue'

// Define page metadata using definePage
definePage({
  name: 'Developers',
  meta: {
    auth: true,
    layout: 'default',
  },
})
</script>
