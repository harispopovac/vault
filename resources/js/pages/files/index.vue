<template>
  <VContainer fluid>
    <VRow>
      <VCol cols="12">
        <VCard>
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <h4 class="text-h4 mb-2">⚡ Files Management</h4>
              <p class="text-body-1 mb-0">
                This page demonstrates the files management functionality.
                It displays files with standard features like pagination, sorting, and basic actions.
              </p>
            </div>
            
            <VChip color="success" variant="tonal" size="large">
              <VIcon icon="tabler-file-upload" start />
              Files Management
            </VChip>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow class="mt-6">
      <VCol cols="12">
        <VCard>
          <VCardText>
            <h5 class="text-h5 mb-3">⬆️ Upload New Files</h5>
            <FileUploadWidget
                :key="demoFiles.length"
                :size="'xl'"
                :collection-name="'demo-files'"
                :model-id="1"
                :model-type="'User'"
                :files="demoFiles"
                :only-images="false"
                :multiple="true"
                :max-file-size="10"
                @update:files="handleFilesUpdate"
                @get-files="refreshFiles"
            />
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>

<script setup>
import { ref } from 'vue'
import { FileUploadWidget } from 'files-module-frontend'
import { $api } from '@/utils/api.js'


// Reactive data for the file upload widget
const isDialogVisible = ref(true)
const demoFiles = ref([])

onMounted(async () => {
  try {
    // Then try to fetch files
    const response = await $api('/files/get')
    demoFiles.value = response.data
  } catch (error) {
    console.error('Error fetching files:', error)
  }
})

// File upload handlers
const handleFilesUpdate = (files) => {
  console.log('Files updated:', files)
  // Refresh the files list after upload
  refreshFiles()
}

const refreshFiles = async () => {
  console.log('Refreshing files...')
  try {
    const response = await $api('/files/get')
    demoFiles.value = response.data || response
    console.log('Files refreshed:', demoFiles.value)
  } catch (error) {
    console.error('Error refreshing files:', error)
  }
}

definePage({
    name: "Files",
    meta: {
        auth: true,
        layout: "default",
    },
})
</script>

<style scoped>
.v-container {
  max-width: 1200px;
}
</style>
