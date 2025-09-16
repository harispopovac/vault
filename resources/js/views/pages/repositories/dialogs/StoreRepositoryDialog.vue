<template>
  <VDialog
    :model-value="modelValue"
    max-width="600"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="closeDialog" />

    <VCard title="Link a New Repository">
      <VCardText>
        Connect your GitHub repository to enable automatic knowledge capture
      </VCardText>

      <!-- Form Content -->
      <VCardText>
        <VForm
          ref="formRef"
          v-model="isFormValid"
          @submit.prevent="handleSubmit"
        >
          <!-- Repository URL -->
          <AppTextField
            v-model="formData.url"
            label="GitHub Repository URL"
            placeholder="https://github.com/username/repository"
            hint="Enter the full URL of your GitHub repository"
            persistent-hint
            :rules="[requiredValidator, urlValidator]"
            prepend-inner-icon="tabler-git-branch"
            :disabled="isLoading"
            class="mb-4"
          />

          <!-- Webhook Setup Instructions -->
          <VAlert
            type="info"
            variant="tonal"
            class="mb-4"
          >
            <template #prepend>
              <VIcon icon="tabler-webhook" />
            </template>
            <VAlertTitle>Webhook Configuration Required</VAlertTitle>
            <div class="mt-2">
              <p class="text-body-2 mb-3">
                After linking your repository, you'll need to configure a webhook in your GitHub repository settings:
              </p>
              <ol class="text-body-2 mb-3 ps-4">
                <li>Go to your repository Settings → Webhooks</li>
                <li>Click "Add webhook"</li>
                <li>Set the Payload URL to:</li>
              </ol>
              <AppTextField
                :model-value="webhookUrl"
                label="Webhook URL"
                readonly
                density="compact"
                variant="outlined"
                class="mb-3"
                append-inner-icon="tabler-copy"
                @click:append-inner="copyWebhookUrl"
              />
              <div class="d-flex align-center gap-2">
                <VIcon icon="tabler-external-link" size="16" />
                <a
                  href="https://docs.github.com/en/developers/webhooks-and-events/webhooks/creating-webhooks"
                  target="_blank"
                  class="text-primary text-decoration-none text-body-2"
                >
                  View GitHub webhook documentation
                </a>
              </div>
            </div>
          </VAlert>

          <!-- Error Alert -->
          <VAlert
            v-if="errorMessage"
            type="error"
            class="mb-4"
            closable
            @click:close="errorMessage = ''"
          >
            {{ errorMessage }}
          </VAlert>

          <!-- Success Alert -->
          <VAlert
            v-if="successMessage"
            type="success"
            class="mb-4"
            closable
            @click:close="successMessage = ''"
          >
            {{ successMessage }}
          </VAlert>
        </VForm>
      </VCardText>

      <VDivider />

      <!-- Dialog Actions -->
      <VCardText class="d-flex justify-space-between align-items-center flex-wrap gap-3">
        <div class="flex-grow-1"></div>

        <VBtn
          variant="tonal"
          color="secondary"
          :disabled="isLoading"
          @click="closeDialog"
        >
          Close
        </VBtn>

        <VBtn
          :loading="isLoading"
          :disabled="!canSubmit"
          @click="handleSubmit"
        >
          Save
        </VBtn>
      </VCardText>
    </VCard>

    <!-- Copy Success Snackbar -->
    <VSnackbar
      v-model="showCopySnackbar"
      timeout="2000"
      color="success"
    >
      Webhook URL copied to clipboard!
    </VSnackbar>
  </VDialog>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { $api } from '@/utils/api'
import { $endpoint } from '@/utils/endpoints'
import DialogCloseBtn from '@/@core/components/DialogCloseBtn.vue'

// Props
const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  repository: {
    type: Object,
    default: null,
  },
})

// Emits
const emit = defineEmits(['update:modelValue', 'saved'])

// Component state
const formRef = ref()
const isFormValid = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const showCopySnackbar = ref(false)

// Form data
const formData = reactive({
  url: '',
})

// Computed
const webhookUrl = computed(() => {
  // TODO: Replace with actual webhook URL from your Laravel backend
  return `${window.location.origin}/api/webhooks/github`
})

const canSubmit = computed(() => {
  if (isLoading.value) return false
  if (!formData.url) return false
  
  // Basic URL validation
  try {
    const url = new URL(formData.url)
    if (url.hostname.includes('github.com')) {
      const pathSegments = url.pathname.split('/').filter(Boolean)
      return pathSegments.length >= 2
    }
  } catch {
    return false
  }
  
  return false
})

// Validation rules
const requiredValidator = value => !!value || 'This field is required'

const urlValidator = value => {
  if (!value) return true

  try {
    const url = new URL(value)
    // Check if it's a GitHub URL
    if (!url.hostname.includes('github.com')) {
      return 'Please enter a valid GitHub repository URL'
    }

    // Check if it has the correct format
    const pathSegments = url.pathname.split('/').filter(Boolean)
    if (pathSegments.length < 2) {
      return 'URL must be in format: https://github.com/username/repository'
    }

    return true
  } catch {
    return 'Please enter a valid URL'
  }
}

// Methods
const resetForm = () => {
  if (formRef.value) {
    formRef.value.reset()
  }

  // Reset form data
  Object.assign(formData, {
    url: '',
  })

  // Reset state
  errorMessage.value = ''
  successMessage.value = ''
  isLoading.value = false
}

const closeDialog = () => {
  emit('update:modelValue', false)
  resetForm()
}


const copyWebhookUrl = async () => {
  try {
    await navigator.clipboard.writeText(webhookUrl.value)
    showCopySnackbar.value = true
  } catch (error) {
    console.error('Failed to copy webhook URL:', error)
  }
}

const handleSubmit = async () => {
  if (!formRef.value) return

  const { valid } = await formRef.value.validate()
  if (!valid) return

  isLoading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    // Make actual API call to link repository
    const response = await $api($endpoint('STORE_REPOSITORY'), {
      method: 'POST',
      body: {
        url: formData.url,
      }
    })

    const savedRepository = response.data

    successMessage.value = 'Repository linked successfully!'

    // Emit the saved event
    emit('saved', savedRepository)

    // Close dialog after a short delay to show success message
    setTimeout(() => {
      emit('update:modelValue', false)
      resetForm()
    }, 1500)
  } catch (error) {
    console.error('Error linking repository:', error)

    if (error.response?.status === 404) {
      errorMessage.value = 'Repository not found. Please check the URL and try again.'
    } else if (error.response?.status === 401) {
      errorMessage.value = 'Authentication failed. Please ensure the repository is public or contact support for private repository access.'
    } else if (error.response?.status === 422) {
      // Validation errors
      const errors = error.response?.data?.errors
      if (errors?.url) {
        errorMessage.value = errors.url[0]
      } else {
        errorMessage.value = error.response?.data?.message || 'Invalid repository URL format.'
      }
    } else {
      errorMessage.value = error.response?.data?.message || 'Failed to link repository. Please try again.'
    }
  } finally {
    isLoading.value = false
  }
}

// Watchers
watch(
  () => props.modelValue,
  (newValue) => {
    if (!newValue) {
      // Reset form when dialog closes
      resetForm()
    }
  },
  { immediate: true }
)
</script>

<style scoped>
.v-card {
  overflow: visible;
}

.v-alert :deep(.v-alert__content) {
  flex: 1;
}

.v-alert ol {
  margin: 0;
}

.v-alert li {
  margin-bottom: 4px;
}
</style>
