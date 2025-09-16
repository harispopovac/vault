<template>
  <VDialog
    :model-value="modelValue"
    max-width="600"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard>
      <!-- Dialog Header -->
      <VCardText class="d-flex align-center justify-space-between">
        <div>
          <h4 class="text-h4 mb-1">
            {{ isEditMode ? 'Edit Developer' : 'Add New Developer' }}
          </h4>
          <p class="text-body-2 mb-0">
            {{ isEditMode 
              ? 'Update developer information and permissions' 
              : 'Add a new team member to your knowledge vault' 
            }}
          </p>
        </div>

        <VBtn 
          icon 
          variant="text" 
          @click="closeDialog"
        >
          <VIcon icon="tabler-x" />
        </VBtn>
      </VCardText>

      <VDivider />

      <!-- Form Content -->
      <VCardText>
        <VForm
          ref="formRef"
          v-model="isFormValid"
          @submit.prevent="handleSubmit"
        >
          <VRow>
            <!-- Name -->
            <VCol cols="12">
              <VTextField
                v-model="formData.name"
                label="Full Name"
                placeholder="John Doe"
                :rules="[requiredValidator, nameValidator]"
                prepend-inner-icon="tabler-user"
                :disabled="isLoading"
              />
            </VCol>

            <!-- Email -->
            <VCol cols="12">
              <VTextField
                v-model="formData.email"
                label="Email Address"
                placeholder="john.doe@example.com"
                type="email"
                :rules="[requiredValidator, emailValidator]"
                prepend-inner-icon="tabler-mail"
                :disabled="isLoading || isEditMode"
              />
            </VCol>

            <!-- Role -->
            <VCol cols="12" md="6">
              <VSelect
                v-model="formData.role"
                label="Role"
                :items="roleOptions"
                :rules="[requiredValidator]"
                prepend-inner-icon="tabler-shield"
                :disabled="isLoading"
              />
            </VCol>

            <!-- GitHub Username -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.githubUsername"
                label="GitHub Username (Optional)"
                placeholder="johndoe"
                prepend-inner-icon="tabler-brand-github"
                :disabled="isLoading"
              />
            </VCol>

            <!-- Repository Access -->
            <VCol cols="12">
              <div class="text-subtitle-2 mb-2">
                <VIcon icon="tabler-git-branch" size="20" class="me-2" />
                Repository Access
              </div>
              <VRadioGroup
                v-model="formData.repositoryAccess"
                :disabled="isLoading"
              >
                <VRadio
                  label="All repositories"
                  value="all"
                  color="primary"
                />
                <VRadio
                  label="Selected repositories only"
                  value="selected"
                  color="primary"
                />
              </VRadioGroup>
            </VCol>

            <!-- Repository Selection (if selected access) -->
            <VCol v-if="formData.repositoryAccess === 'selected'" cols="12">
              <VSelect
                v-model="formData.selectedRepositories"
                label="Select Repositories"
                :items="availableRepositories"
                item-title="name"
                item-value="id"
                multiple
                chips
                closable-chips
                prepend-inner-icon="tabler-folder"
                :disabled="isLoading"
                hint="Choose which repositories this developer can access"
                persistent-hint
              />
            </VCol>

            <!-- Permissions -->
            <VCol cols="12">
              <div class="text-subtitle-2 mb-2">
                <VIcon icon="tabler-lock" size="20" class="me-2" />
                Permissions
              </div>
              <VCheckbox
                v-model="formData.canCreateTriggers"
                label="Create and manage triggers"
                :disabled="isLoading"
                class="mb-2"
              />
              <VCheckbox
                v-model="formData.canManageTemplates"
                label="Create and manage prompt templates"
                :disabled="isLoading"
                class="mb-2"
              />
              <VCheckbox
                v-model="formData.canViewAnalytics"
                label="View analytics and reports"
                :disabled="isLoading"
              />
            </VCol>

            <!-- Send Invitation -->
            <VCol v-if="!isEditMode" cols="12">
              <VCheckbox
                v-model="formData.sendInvitation"
                label="Send invitation email to the developer"
                :disabled="isLoading"
              />
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
      <VCardActions class="pa-4">
        <VSpacer />

        <VBtn
          variant="outlined"
          :disabled="isLoading"
          @click="closeDialog"
        >
          Cancel
        </VBtn>

        <VBtn
          color="primary"
          :loading="isLoading"
          :disabled="!isFormValid"
          @click="handleSubmit"
        >
          <VIcon icon="tabler-device-floppy" start />
          {{ isEditMode ? 'Update' : 'Save' }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'

// Props
const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  developer: {
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

// Form data
const formData = reactive({
  name: '',
  email: '',
  role: '',
  githubUsername: '',
  repositoryAccess: 'all',
  selectedRepositories: [],
  canCreateTriggers: true,
  canManageTemplates: true,
  canViewAnalytics: true,
  sendInvitation: true,
})

// Options
const roleOptions = [
  { title: 'Admin', value: 'Admin' },
  { title: 'Developer', value: 'Developer' },
  { title: 'Reviewer', value: 'Reviewer' },
  { title: 'Viewer', value: 'Viewer' },
]

// Mock repositories - replace with API call
const availableRepositories = [
  { id: 1, name: 'knowledge-vault' },
  { id: 2, name: 'api-backend' },
  { id: 3, name: 'mobile-app' },
  { id: 4, name: 'documentation' },
]

// Computed
const isEditMode = computed(() => !!props.developer?.id)

// Validation rules
const requiredValidator = value => !!value || 'This field is required'

const nameValidator = value => {
  if (!value) return true
  if (value.length < 2) return 'Name must be at least 2 characters'
  if (value.length > 100) return 'Name must be less than 100 characters'
  
  return true
}

const emailValidator = value => {
  if (!value) return true
  
  const emailPattern = /^[^\s@]+@[^\s@][^\s.@]*\.[^\s@]+$/
  return emailPattern.test(value) || 'Please enter a valid email address'
}

// Methods
const resetForm = () => {
  if (formRef.value) {
    formRef.value.reset()
  }

  // Reset form data
  Object.assign(formData, {
    name: '',
    email: '',
    role: '',
    githubUsername: '',
    repositoryAccess: 'all',
    selectedRepositories: [],
    canCreateTriggers: true,
    canManageTemplates: true,
    canViewAnalytics: true,
    sendInvitation: true,
  })

  // Reset state
  errorMessage.value = ''
  successMessage.value = ''
  isLoading.value = false
}

const closeDialog = () => {
  emit('update:modelValue', false)

  // Reset form after a short delay to avoid animation issues
  setTimeout(resetForm, 300)
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
    await new Promise(resolve => setTimeout(resolve, 1000))

    const savedDeveloper = {
      ...formData,
      id: props.developer?.id || Date.now(),
      createdAt: props.developer?.createdAt || new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    }

    // TODO: Make actual API call here
    // const response = await $api.post('/developers', formData)

    successMessage.value = isEditMode.value 
      ? 'Developer updated successfully!' 
      : 'Developer added successfully!'

    // Emit the saved event
    emit('saved', savedDeveloper)

    // Close dialog after a short delay to show success message
    setTimeout(() => {
      closeDialog()
    }, 1500)
  } catch (error) {
    console.error('Error saving developer:', error)
    
    if (error.response?.status === 409) {
      errorMessage.value = 'A developer with this email already exists.'
    } else {
      errorMessage.value = error.response?.data?.message || 'Failed to save developer. Please try again.'
    }
  } finally {
    isLoading.value = false
  }
}

// Watchers
watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue && props.developer) {
      // Populate form with existing developer data
      Object.assign(formData, {
        name: props.developer.name || '',
        email: props.developer.email || '',
        role: props.developer.role || '',
        githubUsername: props.developer.githubUsername || '',
        repositoryAccess: props.developer.repositoryAccess || 'all',
        selectedRepositories: props.developer.selectedRepositories || [],
        canCreateTriggers: props.developer.canCreateTriggers ?? true,
        canManageTemplates: props.developer.canManageTemplates ?? true,
        canViewAnalytics: props.developer.canViewAnalytics ?? true,
        sendInvitation: false, // Never send invitation on edit
      })
    } else if (!newValue) {
      // Reset form when dialog closes
      setTimeout(resetForm, 300)
    }
  },
  { immediate: true }
)

// Watch role changes to adjust default permissions
watch(
  () => formData.role,
  (newRole) => {
    if (!props.developer && newRole) {
      // Set default permissions based on role for new developers
      switch (newRole) {
        case 'Admin':
          formData.canCreateTriggers = true
          formData.canManageTemplates = true
          formData.canViewAnalytics = true
          break
        case 'Developer':
          formData.canCreateTriggers = true
          formData.canManageTemplates = true
          formData.canViewAnalytics = false
          break
        case 'Reviewer':
          formData.canCreateTriggers = false
          formData.canManageTemplates = true
          formData.canViewAnalytics = true
          break
        case 'Viewer':
          formData.canCreateTriggers = false
          formData.canManageTemplates = false
          formData.canViewAnalytics = true
          break
      }
    }
  }
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
