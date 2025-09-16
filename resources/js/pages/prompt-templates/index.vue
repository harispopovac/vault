<template>
  <PromptTemplates />
</template>

<script setup>
// Import the main PromptTemplates component from views
import PromptTemplates from '@/views/pages/prompt-templates/PromptTemplates.vue'

// Define page metadata using definePage
definePage({
  name: 'PromptTemplates',
  meta: {
    auth: true,
    layout: 'default',
  },
})
</script>
