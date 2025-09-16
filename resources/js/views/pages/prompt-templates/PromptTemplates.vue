<template>
  <VContainer fluid>
    <!-- Header Section -->
    <VRow>
      <VCol cols="12">
        <VCard>
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <h4 class="text-h4 mb-1">📝 Prompt Templates</h4>
              <p class="text-body-2 mb-0 text-medium-emphasis">
                Create structured form templates for knowledge capture
              </p>
            </div>

            <VBtn
              color="primary"
              @click="openDialog()"
            >
              <VIcon icon="tabler-plus" start />
              Add Template
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Templates Grid Section -->
    <VRow class="mt-6">
      <!-- Empty State -->
      <VCol v-if="templates.length === 0" cols="12">
        <VCard>
          <VCardText class="text-center py-12">
            <VIcon 
              icon="tabler-template" 
              size="80" 
              color="grey-lighten-1"
              class="mb-4"
            />
            <h5 class="text-h5 text-grey mb-2">No templates yet</h5>
            <p class="text-body-2 text-grey mb-4">
              Create your first template to get started
            </p>
            <VBtn
              color="primary"
              @click="openDialog()"
            >
              <VIcon icon="tabler-plus" start />
              Create Template
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Templates Cards -->
      <VCol
        v-for="template in templates"
        :key="template.id"
        cols="12"
        md="6"
        lg="4"
      >
        <VCard 
          hover
          class="h-100 d-flex flex-column"
        >
          <VCardTitle class="d-flex align-center">
            <VIcon icon="tabler-template" class="me-2" />
            {{ template.name }}
          </VCardTitle>

          <VCardText class="flex-grow-1">
            <p class="text-body-2 template-content">
              {{ template.description || 'No description provided' }}
            </p>
            
            <!-- Field count info -->
            <div class="mt-2">
              <VChip 
                size="small" 
                variant="tonal"
                color="info"
                class="me-2"
              >
                <VIcon icon="tabler-forms" size="14" start />
                {{ template.field_definitions?.length || 0 }} fields
              </VChip>
            </div>
            
            <div class="mt-3">
              <VChip 
                size="small" 
                variant="tonal"
                class="me-2"
              >
                <VIcon icon="tabler-calendar" size="14" start />
                {{ formatDate(template.created_at) }}
              </VChip>
              <VChip 
                v-if="template.usage_count > 0"
                size="small" 
                variant="tonal"
                color="success"
              >
                <VIcon icon="tabler-chart-line" size="14" start />
                Used {{ template.usage_count }} times
              </VChip>
            </div>
          </VCardText>

          <VDivider />

          <VCardActions class="pa-3">
            <VBtn
              variant="text"
              size="small"
              @click="copyTemplate(template)"
            >
              <VIcon icon="tabler-copy" start />
              Copy
            </VBtn>

            <VSpacer />

            <IconBtn
              size="small"
              @click="openDialog(template)"
            >
              <VIcon icon="tabler-edit" />
            </IconBtn>

            <IconBtn
              size="small"
              color="error"
              @click="showDeleteConfirm(template)"
            >
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </VCardActions>
        </VCard>
      </VCol>
    </VRow>

    <!-- Store/Update Dialog -->
    <StoreUpdatePromptTemplateDialog
      v-model="isDialogVisible"
      :template="selectedTemplate"
      @saved="handleTemplateSaved"
    />

    <!-- Snackbar for notifications -->
    <VSnackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="snackbar.timeout"
    >
      {{ snackbar.text }}
    </VSnackbar>

    <!-- Delete Confirmation Dialog -->
    <ConfirmDialog
      :is-dialog-visible="deleteDialog.show"
      :question="deleteDialog.question"
      confirm-label="Delete"
      cancel-label="Cancel"
      confirm-color="error"
      icon="tabler-trash"
      color="error"
      @confirm="confirmDelete"
      @close="deleteDialog.show = false"
    />
  </VContainer>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import StoreUpdatePromptTemplateDialog from './dialogs/StoreUpdatePromptTemplateDialog.vue'
import ConfirmDialog from '@/components/dialogs/ConfirmDialog.vue'

// Component state
const isDialogVisible = ref(false)
const selectedTemplate = ref(null)
const templates = ref([])

// Snackbar state
const snackbar = reactive({
  show: false,
  text: '',
  color: 'success',
  timeout: 3000,
})

// Delete dialog state
const deleteDialog = reactive({
  show: false,
  question: '',
  templateToDelete: null,
})

// Lifecycle
onMounted(() => {
  // Load templates from API
  loadTemplates()
})

// Methods
const loadTemplates = async () => {
  try {
    const response = await $api($endpoint('INDEX_PROMPT_TEMPLATES'))
    templates.value = response.data || []
  } catch (error) {
    console.error('Failed to load templates:', error)
    showNotification('Failed to load templates', 'error')
  }
}

const openDialog = (template = null) => {
  selectedTemplate.value = template ? { ...template } : null
  isDialogVisible.value = true
}

const showDeleteConfirm = (template) => {
  deleteDialog.templateToDelete = template
  deleteDialog.question = `Are you sure you want to delete "${template.name}"? This action cannot be undone.`
  deleteDialog.show = true
}

const confirmDelete = async () => {
  if (!deleteDialog.templateToDelete) return
  
  try {
    await $api($endpoint('DELETE_PROMPT_TEMPLATE', { id: deleteDialog.templateToDelete.id }), {
      method: 'DELETE'
    })
    
    // Remove from local list
    templates.value = templates.value.filter(t => t.id !== deleteDialog.templateToDelete.id)
    showNotification('Template deleted successfully', 'success')
  } catch (error) {
    console.error('Failed to delete template:', error)
    showNotification('Failed to delete template', 'error')
  } finally {
    deleteDialog.show = false
    deleteDialog.templateToDelete = null
  }
}

const handleTemplateSaved = (template) => {
  // If editing, update the existing template
  if (selectedTemplate.value && selectedTemplate.value.id) {
    const index = templates.value.findIndex(t => t.id === selectedTemplate.value.id)
    if (index !== -1) {
      templates.value[index] = template
    }
    showNotification('Template updated successfully', 'success')
  } else {
    // Add new template to the list
    templates.value.unshift(template)
    showNotification('Template created successfully', 'success')
  }
  
  // Reset state
  selectedTemplate.value = null
  isDialogVisible.value = false
}

const copyTemplate = async (template) => {
  try {
    // Create a simplified text representation of the template
    let copyText = `${template.name}\n\n`
    
    if (template.description) {
      copyText += `Description: ${template.description}\n\n`
    }
    
    if (template.field_definitions && template.field_definitions.length > 0) {
      copyText += 'Fields:\n'
      template.field_definitions.forEach((field, index) => {
        copyText += `${index + 1}. ${field.label} (${field.type})`
        if (field.required) copyText += ' *'
        copyText += '\n'
        
        if (field.options && field.options.length > 0) {
          field.options.forEach(option => {
            copyText += `   - ${option}\n`
          })
        }
      })
    }
    
    await navigator.clipboard.writeText(copyText)
    showNotification('Template copied to clipboard', 'success')
    
    // Also increment usage count
    await $api($endpoint('USE_PROMPT_TEMPLATE', { id: template.id }), {
      method: 'POST'
    })
    
    // Update local usage count
    const templateIndex = templates.value.findIndex(t => t.id === template.id)
    if (templateIndex !== -1) {
      templates.value[templateIndex].usage_count = (templates.value[templateIndex].usage_count || 0) + 1
    }
  } catch (error) {
    console.error('Failed to copy:', error)
    showNotification('Failed to copy template', 'error')
  }
}

const showNotification = (text, color = 'success') => {
  snackbar.text = text
  snackbar.color = color
  snackbar.show = true
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}
</script>

<style scoped>
.v-container {
  max-width: 1400px;
}

.template-content {
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  min-height: 60px;
}

.v-card {
  transition: transform 0.2s;
}

.v-card:hover {
  transform: translateY(-2px);
}
</style>
