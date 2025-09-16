<template>
  <VDialog
    :model-value="modelValue"
    max-width="700"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="closeDialog" />

    <VCard :title="isEditMode ? 'Edit Repository' : 'Connect Repository'">
      <VCardText>
        {{ isEditMode 
          ? 'Update repository connection settings' 
          : 'Connect a GitHub repository to enable automatic knowledge capture' 
        }}
      </VCardText>

      <!-- Form Content -->
      <VCardText>
        <VForm
          ref="formRef"
          v-model="isFormValid"
          @submit.prevent="handleSubmit"
        >
          <VRow>
            <!-- Repository URL -->
            <VCol cols="12">
              <VTextField
                v-model="formData.url"
                label="Repository URL"
                placeholder="https://github.com/username/repository"
                hint="Enter the full URL of your GitHub repository"
                persistent-hint
                :rules="[requiredValidator, urlValidator]"
                prepend-inner-icon="tabler-git-branch"
                :disabled="isLoading || isEditMode"
              />
            </VCol>

            <!-- Repository Name (Auto-filled) -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.name"
                label="Repository Name"
                placeholder="repository-name"
                hint="Extracted from the repository URL"
                persistent-hint
                :rules="[requiredValidator]"
                prepend-inner-icon="tabler-folder"
                :disabled="isLoading"
                :readonly="!isEditMode"
              />
            </VCol>

            <!-- Primary Language -->
            <VCol cols="12" md="6">
              <VSelect
                v-model="formData.language"
                label="Primary Language"
                :items="languageOptions"
                hint="Main programming language used"
                persistent-hint
                prepend-inner-icon="tabler-code"
                :disabled="isLoading"
              />
            </VCol>

            <!-- Description -->
            <VCol cols="12">
              <VTextarea
                v-model="formData.description"
                label="Description"
                placeholder="Brief description of the repository..."
                hint="Describe the purpose and functionality of this repository"
                persistent-hint
                :rules="[descriptionValidator]"
                prepend-inner-icon="tabler-align-left"
                :disabled="isLoading"
                rows="3"
                auto-grow
                counter
                maxlength="500"
              />
            </VCol>

            <!-- Access Token -->
            <VCol cols="12">
              <VTextField
                v-model="formData.accessToken"
                label="Personal Access Token (Optional)"
                placeholder="ghp_xxxxxxxxxxxxxxxxxxxx"
                hint="Required for private repositories. Create one at GitHub Settings > Developer settings"
                persistent-hint
                :type="showToken ? 'text' : 'password'"
                :rules="[tokenValidator]"
                prepend-inner-icon="tabler-key"
                :append-inner-icon="showToken ? 'tabler-eye-off' : 'tabler-eye'"
                :disabled="isLoading"
                @click:append-inner="showToken = !showToken"
              />
            </VCol>

            <!-- Tracking Options -->
            <VCol cols="12">
              <div class="text-subtitle-2 mb-2">
                <VIcon icon="tabler-settings" size="20" class="me-2" />
                Tracking Options
              </div>
              <VCheckbox
                v-model="formData.trackCommits"
                label="Track commits and code changes"
                hint="Capture knowledge from commit messages and code diffs"
                persistent-hint
                :disabled="isLoading"
                class="mb-2"
              />
              <VCheckbox
                v-model="formData.trackIssues"
                label="Track issues and discussions"
                hint="Monitor GitHub issues for problem-solving insights"
                persistent-hint
                :disabled="isLoading"
                class="mb-2"
              />
              <VCheckbox
                v-model="formData.trackPullRequests"
                label="Track pull requests"
                hint="Capture knowledge from code reviews and PR discussions"
                persistent-hint
                :disabled="isLoading"
              />
            </VCol>

            <!-- Instructions Alert -->
            <VCol cols="12">
              <VAlert
                type="info"
                variant="tonal"
                closable
              >
                <template #prepend>
                  <VIcon icon="tabler-info-circle" />
                </template>
                <VAlertTitle>Setup Instructions</VAlertTitle>
                <ol class="mt-2 mb-0 ps-4">
                  <li>For private repositories, generate a Personal Access Token with 'repo' scope</li>
                  <li>After connecting, configure webhooks in your GitHub repository settings</li>
                  <li>Select the events you want to track for knowledge capture</li>
                </ol>
              </VAlert>
            </VCol>
          </VRow>
        </VForm>

        <!-- Error Alert -->
        <VAlert
          v-if="errorMessage"
          type="error"
          class="mt-4"
          closable
          @click:close="errorMessage = ''"
        >
          {{ errorMessage }}
        </VAlert>

        <!-- Success Alert -->
        <VAlert
          v-if="successMessage"
          type="success"
          class="mt-4"
          closable
          @click:close="successMessage = ''"
        >
          {{ successMessage }}
        </VAlert>
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
          :disabled="!isFormValid"
          @click="handleSubmit"
        >
          {{ isEditMode ? 'Update' : 'Save' }}
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
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
const showToken = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

// Form data
const formData = reactive({
  url: '',
  name: '',
  language: '',
  description: '',
  accessToken: '',
  trackCommits: true,
  trackIssues: true,
  trackPullRequests: true,
})

// Language options
const languageOptions = [
  'JavaScript',
  'TypeScript',
  'Vue.js',
  'React',
  'Python',
  'Java',
  'PHP',
  'Laravel',
  'C#',
  'Go',
  'Ruby',
  'Swift',
  'Kotlin',
  'Other',
]

// Computed
const isEditMode = computed(() => !!props.repository?.id)

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

const descriptionValidator = value => {
  if (!value) return true
  if (value.length > 500) return 'Description must be less than 500 characters'
  
  return true
}

const tokenValidator = value => {
  if (!value) return true
  
  // Basic GitHub token format validation
  if (!value.startsWith('ghp_') && !value.startsWith('github_pat_')) {
    return 'Invalid token format. GitHub tokens start with "ghp_" or "github_pat_"'
  }
  
  if (value.length < 40) {
    return 'Token appears to be incomplete'
  }
  
  return true
}

// Methods
const resetForm = () => {
  if (formRef.value) {
    formRef.value.reset()
  }

  // Reset form data
  Object.assign(formData, {
    url: '',
    name: '',
    language: '',
    description: '',
    accessToken: '',
    trackCommits: true,
    trackIssues: true,
    trackPullRequests: true,
  })

  // Reset state
  errorMessage.value = ''
  successMessage.value = ''
  isLoading.value = false
  showToken.value = false
}

const closeDialog = () => {
  emit('update:modelValue', false)

  // Reset form after a short delay to avoid animation issues
  setTimeout(resetForm, 300)
}

const extractRepoName = (url) => {
  try {
    const urlObj = new URL(url)
    const pathSegments = urlObj.pathname.split('/').filter(Boolean)
    if (pathSegments.length >= 2) {
      return pathSegments[pathSegments.length - 1].replace('.git', '')
    }
  } catch {
    // Invalid URL
  }
  return ''
}

const handleSubmit = async () => {
  if (!formRef.value) return

  const { valid } = await formRef.value.validate()
  if (!valid) return

  isLoading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    // Simulate API call - replace with actual implementation
    await new Promise(resolve => setTimeout(resolve, 1500))

    // TODO: Validate repository exists and user has access
    // const isValid = await validateRepository(formData.url, formData.accessToken)
    
    const savedRepository = {
      ...formData,
      id: props.repository?.id || Date.now(),
      createdAt: props.repository?.createdAt || new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    }

    // TODO: Make actual API call here
    // const response = await $api.post('/repositories', formData)

    successMessage.value = isEditMode.value 
      ? 'Repository updated successfully!' 
      : 'Repository connected successfully!'

    // Emit the saved event
    emit('saved', savedRepository)

    // Close dialog after a short delay to show success message
    setTimeout(() => {
      closeDialog()
    }, 1500)
  } catch (error) {
    console.error('Error saving repository:', error)
    
    if (error.response?.status === 404) {
      errorMessage.value = 'Repository not found. Please check the URL and try again.'
    } else if (error.response?.status === 401) {
      errorMessage.value = 'Authentication failed. Please check your access token for private repositories.'
    } else {
      errorMessage.value = error.response?.data?.message || 'Failed to connect repository. Please try again.'
    }
  } finally {
    isLoading.value = false
  }
}

// Watchers
watch(
  () => formData.url,
  (newUrl) => {
    if (newUrl && !isEditMode.value) {
      // Auto-extract repository name from URL
      const repoName = extractRepoName(newUrl)
      if (repoName) {
        formData.name = repoName
      }
    }
  }
)

watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue && props.repository) {
      // Populate form with existing repository data
      Object.assign(formData, {
        url: props.repository.url || '',
        name: props.repository.name || '',
        language: props.repository.language || '',
        description: props.repository.description || '',
        accessToken: '', // Never populate token for security
        trackCommits: props.repository.trackCommits ?? true,
        trackIssues: props.repository.trackIssues ?? true,
        trackPullRequests: props.repository.trackPullRequests ?? true,
      })
    } else if (!newValue) {
      // Reset form when dialog closes
      setTimeout(resetForm, 300)
    }
  },
  { immediate: true }
)
</script>

<style scoped>
.v-card {
  overflow: visible;
}

.v-checkbox {
  margin-top: 0;
}

.v-checkbox :deep(.v-input__details) {
  padding-inline-start: 32px;
}
</style>
