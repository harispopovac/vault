<template>
  <VDialog
    :model-value="modelValue"
    max-width="900"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard>
      <!-- Dialog Header -->
      <VCardText class="d-flex align-center justify-space-between">
        <div>
          <h4 class="text-h4 mb-1">
            {{ isEditMode ? 'Edit Trigger' : 'Create New Trigger' }}
          </h4>
          <p class="text-body-2 mb-0">
            {{ isEditMode 
              ? 'Update your trigger configuration' 
              : 'Set up automated knowledge capture for your repository' 
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

      <!-- Step Progress -->
      <VCardText class="pb-0">
        <VStepper
          v-model="currentStep"
          :items="steps"
          hide-actions
          flat
        >
          <template #item.1>
            <VCard flat>
              <VCardTitle class="text-h5 mb-4">
                <VIcon icon="tabler-git-branch" class="me-2" />
                Repository & Prompt Selection
              </VCardTitle>
              
              <VForm ref="step1Form" v-model="step1Valid">
                <VRow>
                  <!-- Trigger Name -->
                  <VCol cols="12" md="6">
                    <VTextField
                      v-model="formData.name"
                      label="Trigger Name"
                      placeholder="e.g., Main Branch Knowledge Capture"
                      :rules="[requiredValidator]"
                      :disabled="isLoading"
                    />
                  </VCol>

                  <!-- Description -->
                  <VCol cols="12" md="6">
                    <VTextField
                      v-model="formData.description"
                      label="Description (Optional)"
                      placeholder="Brief description of this trigger"
                      :disabled="isLoading"
                    />
                  </VCol>

                  <!-- Repository Selection -->
                  <VCol cols="12" md="6">
                    <VAutocomplete
                      v-model="formData.repository_id"
                      :items="repositories"
                      item-title="name"
                      item-value="id"
                      label="Repository"
                      placeholder="Select a repository"
                      :rules="[requiredValidator]"
                      :loading="loadingRepositories"
                      :disabled="isLoading"
                      prepend-inner-icon="tabler-git-branch"
                    >
                      <template #item="{ props, item }">
                        <VListItem v-bind="props">
                          <template #prepend>
                            <VIcon icon="tabler-git-branch" />
                          </template>
                          <VListItemTitle>{{ item.raw.name }}</VListItemTitle>
                          <VListItemSubtitle>{{ item.raw.owner_id }}</VListItemSubtitle>
                        </VListItem>
                      </template>
                    </VAutocomplete>
                  </VCol>

                  <!-- Prompt Selection -->
                  <VCol cols="12" md="6">
                    <VAutocomplete
                      v-model="formData.prompt_id"
                      :items="prompts"
                      item-title="name"
                      item-value="id"
                      label="Prompt Template"
                      placeholder="Select a prompt template"
                      :rules="[requiredValidator]"
                      :loading="loadingPrompts"
                      :disabled="isLoading"
                      prepend-inner-icon="tabler-message-circle"
                    >
                      <template #item="{ props, item }">
                        <VListItem v-bind="props">
                          <template #prepend>
                            <VIcon icon="tabler-message-circle" />
                          </template>
                          <VListItemTitle>{{ item.raw.name }}</VListItemTitle>
                          <VListItemSubtitle>{{ item.raw.description || 'No description' }}</VListItemSubtitle>
                        </VListItem>
                      </template>
                    </VAutocomplete>
                  </VCol>
                </VRow>
              </VForm>
            </VCard>
          </template>

          <template #item.2>
            <VCard flat>
              <VCardTitle class="text-h5 mb-4">
                <VIcon icon="tabler-webhook" class="me-2" />
                GitHub Events Configuration
              </VCardTitle>
              
              <VForm ref="step2Form" v-model="step2Valid">
                <VRow>
                  <!-- GitHub Events -->
                  <VCol cols="12">
                    <VLabel class="text-body-1 mb-2">GitHub Events to Monitor</VLabel>
                    <VCheckboxBtnGroup
                      v-model="formData.github_events"
                      mandatory
                      multiple
                      :disabled="isLoading"
                    >
                      <VCheckboxBtn
                        v-for="event in availableEvents"
                        :key="event.value"
                        :value="event.value"
                        :text="event.title"
                        color="primary"
                      >
                        <template #prepend>
                          <VIcon :icon="event.icon" class="me-2" />
                        </template>
                      </VCheckboxBtn>
                    </VCheckboxBtnGroup>
                  </VCol>

                  <!-- Event Filters -->
                  <VCol cols="12" v-if="formData.github_events.includes('push')">
                    <VLabel class="text-body-1 mb-2">Branch Filters (Optional)</VLabel>
                    <VCombobox
                      v-model="branchFilters"
                      label="Specific Branches"
                      placeholder="main, develop, feature/*"
                      hint="Leave empty to monitor all branches, or specify branch names/patterns"
                      persistent-hint
                      multiple
                      chips
                      :disabled="isLoading"
                    />
                  </VCol>

                  <VCol cols="12" v-if="formData.github_events.includes('pull_request')">
                    <VLabel class="text-body-1 mb-2">Pull Request Actions</VLabel>
                    <VCheckboxBtnGroup
                      v-model="prActions"
                      multiple
                      :disabled="isLoading"
                    >
                      <VCheckboxBtn
                        v-for="action in availablePRActions"
                        :key="action.value"
                        :value="action.value"
                        :text="action.title"
                        color="success"
                      />
                    </VCheckboxBtnGroup>
                  </VCol>

                  <!-- Delivery Delay -->
                  <VCol cols="12" md="6">
                    <VSlider
                      v-model="formData.delivery_delay_minutes"
                      :min="0"
                      :max="60"
                      :step="5"
                      label="Delivery Delay (minutes)"
                      hint="Delay before sending prompts to users"
                      persistent-hint
                      thumb-label
                      :disabled="isLoading"
                    />
                  </VCol>
                </VRow>
              </VForm>
            </VCard>
          </template>

          <template #item.3>
            <VCard flat>
              <VCardTitle class="text-h5 mb-4">
                <VIcon icon="tabler-users" class="me-2" />
                User Targeting
              </VCardTitle>
              
              <VForm ref="step3Form" v-model="step3Valid">
                <VRow>
                  <!-- Target Type -->
                  <VCol cols="12">
                    <VRadioGroup
                      v-model="formData.target_type"
                      :rules="[requiredValidator]"
                      :disabled="isLoading"
                    >
                      <VRadio
                        value="all"
                        label="All repository users"
                        color="primary"
                      />
                      <VRadio
                        value="specific_users"
                        label="Specific users"
                        color="primary"
                      />
                      <VRadio
                        value="roles"
                        label="Users with specific roles"
                        color="primary"
                      />
                    </VRadioGroup>
                  </VCol>

                  <!-- Specific Users Selection -->
                  <VCol cols="12" v-if="formData.target_type === 'specific_users'">
                    <VAutocomplete
                      v-model="formData.target_users"
                      :items="users"
                      item-title="fname"
                      item-value="id"
                      label="Select Users"
                      placeholder="Choose users to target"
                      multiple
                      chips
                      :loading="loadingUsers"
                      :disabled="isLoading"
                    >
                      <template #item="{ props, item }">
                        <VListItem v-bind="props">
                          <template #prepend>
                            <VAvatar size="32">
                              {{ item.raw.fname?.charAt(0) }}{{ item.raw.sname?.charAt(0) }}
                            </VAvatar>
                          </template>
                          <VListItemTitle>{{ item.raw.fname }} {{ item.raw.sname }}</VListItemTitle>
                          <VListItemSubtitle>{{ item.raw.email }}</VListItemSubtitle>
                        </VListItem>
                      </template>
                    </VAutocomplete>
                  </VCol>

                  <!-- Roles Selection -->
                  <VCol cols="12" v-if="formData.target_type === 'roles'">
                    <VAutocomplete
                      v-model="formData.target_roles"
                      :items="roles"
                      item-title="name"
                      item-value="id"
                      label="Select Roles"
                      placeholder="Choose roles to target"
                      multiple
                      chips
                      :loading="loadingRoles"
                      :disabled="isLoading"
                    />
                  </VCol>
                </VRow>
              </VForm>
            </VCard>
          </template>

          <template #item.4>
            <VCard flat>
              <VCardTitle class="text-h5 mb-4">
                <VIcon icon="tabler-check" class="me-2" />
                Review & Create
              </VCardTitle>
              
              <!-- Summary Cards -->
              <VRow>
                <VCol cols="12" md="6">
                  <VCard variant="tonal" color="primary">
                    <VCardText>
                      <h6 class="text-h6 mb-2">Trigger Details</h6>
                      <p class="mb-1"><strong>Name:</strong> {{ formData.name }}</p>
                      <p class="mb-1"><strong>Description:</strong> {{ formData.description || 'None' }}</p>
                      <p class="mb-0"><strong>Repository:</strong> {{ selectedRepository?.name || 'Not selected' }}</p>
                    </VCardText>
                  </VCard>
                </VCol>

                <VCol cols="12" md="6">
                  <VCard variant="tonal" color="success">
                    <VCardText>
                      <h6 class="text-h6 mb-2">Events & Targeting</h6>
                      <p class="mb-1"><strong>Events:</strong> {{ formData.github_events.join(', ') }}</p>
                      <p class="mb-1"><strong>Targeting:</strong> {{ getTargetingSummary() }}</p>
                      <p class="mb-0"><strong>Delay:</strong> {{ formData.delivery_delay_minutes }}min</p>
                    </VCardText>
                  </VCard>
                </VCol>
              </VRow>

              <!-- Webhook Info -->
              <VAlert
                type="info"
                variant="tonal"
                class="mt-4"
              >
                <template #prepend>
                  <VIcon icon="tabler-info-circle" />
                </template>
                <VAlertTitle>Webhook Setup</VAlertTitle>
                A GitHub webhook will be automatically configured for this repository when you create the trigger.
              </VAlert>
            </VCard>
          </template>
        </VStepper>
      </VCardText>

      <!-- Error/Success Messages -->
      <VCardText v-if="errorMessage || successMessage" class="pt-0">
        <VAlert
          v-if="errorMessage"
          type="error"
          closable
          @click:close="errorMessage = ''"
        >
          {{ errorMessage }}
        </VAlert>

        <VAlert
          v-if="successMessage"
          type="success"
          closable
          @click:close="successMessage = ''"
        >
          {{ successMessage }}
        </VAlert>
      </VCardText>

      <VDivider />

      <!-- Dialog Actions -->
      <VCardActions class="pa-4">
        <VBtn
          v-if="currentStep > 1"
          variant="outlined"
          :disabled="isLoading"
          @click="previousStep"
        >
          <VIcon icon="tabler-arrow-left" start />
          Previous
        </VBtn>

        <VSpacer />

        <VBtn
          variant="outlined"
          :disabled="isLoading"
          @click="closeDialog"
        >
          Cancel
        </VBtn>

        <VBtn
          v-if="currentStep < 4"
          color="primary"
          :disabled="!canProceed"
          @click="nextStep"
        >
          Next
          <VIcon icon="tabler-arrow-right" end />
        </VBtn>

        <VBtn
          v-else
          color="primary"
          :loading="isLoading"
          :disabled="!canCreate"
          @click="handleSubmit"
        >
          <VIcon icon="tabler-check" start />
          {{ isEditMode ? 'Update Trigger' : 'Create Trigger' }}
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
  trigger: {
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
const showSecret = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

// Form data
const formData = reactive({
  repositoryUrl: '',
  webhookSecret: '',
})

// Computed
const isEditMode = computed(() => !!props.trigger?.id)

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

const secretValidator = value => {
  if (!value) return true
  if (value.length < 16) return 'Webhook secret should be at least 16 characters for security'
  
  return true
}

// Methods
const resetForm = () => {
  if (formRef.value) {
    formRef.value.reset()
  }

  // Reset form data
  Object.assign(formData, {
    repositoryUrl: '',
    webhookSecret: '',
  })

  // Reset state
  errorMessage.value = ''
  successMessage.value = ''
  isLoading.value = false
  showSecret.value = false
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

    const savedTrigger = {
      ...formData,
      id: props.trigger?.id || Date.now(),
      createdAt: props.trigger?.createdAt || new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    }

    // TODO: Make actual API call here
    // const response = await $api.post('/triggers', formData)

    successMessage.value = isEditMode.value 
      ? 'Trigger updated successfully!' 
      : 'Trigger created successfully!'

    // Emit the saved event
    emit('saved', savedTrigger)

    // Close dialog after a short delay to show success message
    setTimeout(() => {
      closeDialog()
    }, 1500)
  } catch (error) {
    console.error('Error saving trigger:', error)
    errorMessage.value = error.response?.data?.message || 'Failed to save trigger. Please try again.'
  } finally {
    isLoading.value = false
  }
}

// Watchers
watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue && props.trigger) {
      // Populate form with existing trigger data
      Object.assign(formData, {
        repositoryUrl: props.trigger.repositoryUrl || '',
        webhookSecret: props.trigger.webhookSecret || '',
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
</style>
