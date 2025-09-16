<template>
  <VDialog
    :model-value="modelValue"
    max-width="1000"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="closeDialog" />

    <VCard :title="isEditMode ? 'Edit Trigger' : 'Create New Trigger'">
      <VCardText>
        {{ isEditMode
          ? 'Update your trigger configuration'
          : 'Set up automated knowledge capture for your repository'
        }}
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
                Repository Selection
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
                    <VRow>
                      <VCol
                        v-for="event in availableEvents"
                        :key="event.value"
                        cols="12"
                        sm="6"
                        md="4"
                      >
                        <VCheckbox
                          v-model="formData.github_events"
                          :value="event.value"
                          :label="event.title"
                          color="primary"
                          :disabled="isLoading"
                        >
                          <template #prepend>
                            <VIcon :icon="event.icon" class="me-2" />
                          </template>
                        </VCheckbox>
                      </VCol>
                    </VRow>
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
                    <VRow>
                      <VCol
                        v-for="action in availablePRActions"
                        :key="action.value"
                        cols="12"
                        sm="6"
                        md="3"
                      >
                        <VCheckbox
                          v-model="prActions"
                          :value="action.value"
                          :label="action.title"
                          color="success"
                          :disabled="isLoading"
                        />
                      </VCol>
                    </VRow>
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
                      <!-- TODO: Enable after implementing users/roles endpoints -->
                      <!-- <VRadio
                        value="specific_users"
                        label="Specific users"
                        color="primary"
                        disabled
                      />
                      <VRadio
                        value="roles"
                        label="Users with specific roles"
                        color="primary"
                        disabled
                      /> -->
                    </VRadioGroup>
                    <p class="text-body-2 text-medium-emphasis mt-2">
                      Currently only "All repository users" targeting is supported
                    </p>
                  </VCol>

                  <!-- Specific Users Selection (Disabled) -->
                  <!-- <VCol cols="12" v-if="formData.target_type === 'specific_users'">
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

                  <!-- Roles Selection (Disabled) -->
                  <!-- <VCol cols="12" v-if="formData.target_type === 'roles'">
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
                  </VCol> -->
                </VRow>
              </VForm>
            </VCard>
          </template>

          <template #item.4>
            <VCard flat>
              <VCardTitle class="text-h5 mb-4">
                <VIcon icon="tabler-message-circle" class="me-2" />
                Prompt Creation
              </VCardTitle>

              <!-- Template Selection -->
              <VRow class="mb-6">
                <VCol cols="12">
                  <VAutocomplete
                    v-model="formData.prompt_id"
                    :items="prompts"
                    item-title="name"
                    item-value="id"
                    label="Use Existing Template (Optional)"
                    placeholder="Search templates or leave empty to create custom"
                    :loading="loadingPrompts"
                    :disabled="isLoading"
                    prepend-inner-icon="tabler-template"
                    clearable
                  >
                    <template #item="{ props, item }">
                      <VListItem v-bind="props">
                        <template #prepend>
                          <VIcon icon="tabler-template" />
                        </template>
                        <VListItemTitle>{{ item.raw.name }}</VListItemTitle>
                        <VListItemSubtitle>{{ item.raw.description || 'No description' }}</VListItemSubtitle>
                      </VListItem>
                    </template>
                  </VAutocomplete>
                  <p class="text-body-2 text-medium-emphasis mt-1">
                    Select an existing template or create a custom prompt below
                  </p>
                </VCol>
              </VRow>

              <div v-if="formData.prompt_id">
                <!-- Template Selected -->
                <VAlert type="info" variant="tonal" class="mb-4">
                  <VAlertTitle>Using Template</VAlertTitle>
                  You have selected a prompt template. The form will be generated based on the template's field definitions.
                </VAlert>
              </div>

              <div v-else>
                <!-- Inline Prompt Creation -->
                <VForm ref="step4Form" v-model="step4Valid">
                  <VRow>
                    <VCol cols="12" md="6">
                      <VTextField
                        v-model="formData.inline_prompt_name"
                        label="Prompt Name"
                        placeholder="e.g., Code Review Prompt"
                        :rules="[requiredValidator]"
                      />
                    </VCol>
                    <VCol cols="12" md="6">
                      <VTextField
                        v-model="formData.inline_prompt_description"
                        label="Description (Optional)"
                        placeholder="Brief description of this prompt"
                      />
                    </VCol>
                  </VRow>
                </VForm>

                <!-- Field Builder -->
                <VDivider class="my-6" />

                <div>
                  <div class="d-flex align-center justify-space-between mb-4">
                    <div>
                      <h5 class="text-h5 mb-1">Form Fields</h5>
                      <p class="text-body-2 mb-0 text-medium-emphasis">
                        Design the structure of your knowledge capture form
                      </p>
                    </div>

                    <VMenu>
                      <template #activator="{ props }">
                        <VBtn
                          color="primary"
                          v-bind="props"
                          :disabled="isLoading"
                        >
                          <VIcon icon="tabler-plus" start />
                          Add Field
                          <VIcon icon="tabler-chevron-down" end />
                        </VBtn>
                      </template>

                      <VList>
                        <VListItem
                          @click="addField('textarea')"
                          prepend-icon="tabler-forms"
                        >
                          <VListItemTitle>Text Area</VListItemTitle>
                          <VListItemSubtitle>Multi-line text input</VListItemSubtitle>
                        </VListItem>

                        <VListItem
                          @click="addField('text')"
                          prepend-icon="tabler-forms"
                        >
                          <VListItemTitle>Text Field</VListItemTitle>
                          <VListItemSubtitle>Single line text input</VListItemSubtitle>
                        </VListItem>

                        <VListItem
                          @click="addField('select')"
                          prepend-icon="tabler-checkbox"
                        >
                          <VListItemTitle>Select</VListItemTitle>
                          <VListItemSubtitle>Dropdown selection</VListItemSubtitle>
                        </VListItem>
                      </VList>
                    </VMenu>
                  </div>

                  <!-- Field List -->
                  <div v-if="formData.inline_prompt_field_definitions.length === 0" class="text-center py-6">
                    <VIcon
                      icon="tabler-forms"
                      size="48"
                      color="grey-lighten-1"
                      class="mb-3"
                    />
                    <p class="text-body-1 text-grey mb-2">No fields added yet</p>
                    <p class="text-body-2 text-grey">Add fields above to design your knowledge capture form</p>
                  </div>

                  <VRow v-else>
                    <VCol
                      v-for="(field, index) in formData.inline_prompt_field_definitions"
                      :key="index"
                      cols="12"
                    >
                      <VCard variant="tonal" class="pa-4">
                        <div class="d-flex align-center justify-space-between mb-3">
                          <VChip :color="getFieldTypeColor(field.type)" size="small">
                            {{ getFieldTypeLabel(field.type) }}
                          </VChip>
                          <VBtn
                            icon
                            variant="text"
                            size="small"
                            color="error"
                            @click="removeField(index)"
                          >
                            <VIcon icon="tabler-trash" />
                          </VBtn>
                        </div>

                        <VRow>
                          <VCol cols="12" md="6">
                            <VTextField
                              v-model="field.label"
                              label="Field Label"
                              placeholder="Enter field label"
                              :rules="[requiredValidator]"
                            />
                          </VCol>
                          <VCol cols="12" md="6">
                            <VTextField
                              v-model="field.placeholder"
                              label="Placeholder (Optional)"
                              placeholder="Enter placeholder text"
                            />
                          </VCol>
                          <VCol cols="12">
                            <VCheckbox
                              v-model="field.required"
                              label="Required field"
                            />
                          </VCol>

                          <!-- Options for select field -->
                          <VCol v-if="field.type === 'select'" cols="12">
                            <VTextarea
                              v-model="field.optionsText"
                              label="Options (one per line)"
                              placeholder="Option 1&#10;Option 2&#10;Option 3"
                              rows="3"
                              @input="updateFieldOptions(field)"
                            />
                          </VCol>
                        </VRow>
                      </VCard>
                    </VCol>
                  </VRow>
                </div>
              </div>
            </VCard>
          </template>

          <template #item.5>
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

              <VRow class="mt-4">
                <VCol cols="12">
                  <VCard variant="tonal" color="info">
                    <VCardText>
                      <h6 class="text-h6 mb-2">
                        <VIcon icon="tabler-message-circle" class="me-2" />
                        Prompt Information
                      </h6>
                      <div v-if="formData.prompt_id">
                        <p class="mb-1"><strong>Prompt ID:</strong> {{ formData.prompt_id }}</p>
                        <p v-if="formData.inline_prompt_name" class="mb-1">
                          <strong>Name:</strong> {{ formData.inline_prompt_name }}
                        </p>
                        <p v-if="formData.inline_prompt_description" class="mb-1">
                          <strong>Description:</strong> {{ formData.inline_prompt_description }}
                        </p>
                        <p v-if="formData.inline_prompt_field_definitions.length > 0" class="mb-0">
                          <strong>Fields:</strong> {{ formData.inline_prompt_field_definitions.length }} field(s) defined
                        </p>
                      </div>
                      <div v-else>
                        <p class="mb-0 text-warning">⚠️ No prompt configured - this may cause issues</p>
                      </div>
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
      <VCardText class="d-flex justify-space-between align-items-center flex-wrap gap-3">
        <VBtn
          v-if="currentStep > 1"
          variant="outlined"
          :disabled="isLoading"
          @click="previousStep"
        >
          <VIcon icon="tabler-arrow-left" start />
          Previous
        </VBtn>
        <div v-else class="flex-grow-1"></div>

        <div class="d-flex gap-3">
          <VBtn
            variant="tonal"
            color="secondary"
            :disabled="isLoading"
            @click="closeDialog"
          >
            Cancel
          </VBtn>

          <VBtn
            v-if="currentStep < 5"
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
            {{ isEditMode ? 'Update Trigger' : 'Create Trigger' }}
          </VBtn>
        </div>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
import { computed, reactive, ref, watch, onMounted } from 'vue'
import { $api } from '@/utils/api'
import { $endpoint } from '@/utils/endpoints'
import { useSnackbarStore } from '@/stores/snackbar'

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

// Composables
const snackbar = useSnackbarStore()

// Component state
const currentStep = ref(1)
const step1Form = ref()
const step2Form = ref()
const step3Form = ref()
const step4Form = ref()
const step1Valid = ref(false)
const step2Valid = ref(true) // GitHub events are optional initially
const step3Valid = ref(true) // Target type has default
const step4Valid = ref(true) // Prompt creation validation
const isLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

// Data loading states
const loadingRepositories = ref(false)
const loadingPrompts = ref(false)
// TODO: Add loadingUsers and loadingRoles when needed

// Data arrays
const repositories = ref([])
const prompts = ref([])
// TODO: Add users and roles arrays when endpoints are implemented

// Additional form fields
const branchFilters = ref([])
const prActions = ref(['opened', 'closed'])

// Form data
const formData = reactive({
  name: '',
  description: '',
  repository_id: '',
  prompt_id: '',
  inline_prompt_name: '',
  inline_prompt_description: '',
  inline_prompt_content: '',
  inline_prompt_field_definitions: [],
  github_events: ['push'],
  event_filters: {},
  target_type: 'all',
  target_users: [],
  target_roles: [],
  delivery_delay_minutes: 0,
})

// Step configuration
const steps = [
  { title: 'Repository', icon: 'tabler-git-branch' },
  { title: 'GitHub Events', icon: 'tabler-webhook' },
  { title: 'User Targeting', icon: 'tabler-users' },
  { title: 'Prompt Creation', icon: 'tabler-message-circle' },
  { title: 'Review & Create', icon: 'tabler-check' },
]

// Available GitHub events
const availableEvents = [
  { value: 'push', title: 'Push', icon: 'tabler-git-push' },
  { value: 'pull_request', title: 'Pull Request', icon: 'tabler-git-pull-request' },
  { value: 'issues', title: 'Issues', icon: 'tabler-bug' },
  { value: 'release', title: 'Release', icon: 'tabler-rocket' },
  { value: 'create', title: 'Create Branch/Tag', icon: 'tabler-git-branch' },
  { value: 'delete', title: 'Delete Branch/Tag', icon: 'tabler-trash' },
]

// Available PR actions
const availablePRActions = [
  { value: 'opened', title: 'Opened' },
  { value: 'closed', title: 'Closed' },
  { value: 'reopened', title: 'Reopened' },
  { value: 'synchronize', title: 'Updated' },
]

// Computed
const isEditMode = computed(() => !!props.trigger?.id)

const selectedRepository = computed(() => {
  return repositories.value.find(repo => repo.id === formData.repository_id)
})

const canProceed = computed(() => {
  
  switch (currentStep.value) {
    case 1:
      // Step 1: Just need name and repository
      const step1Can = formData.name && formData.repository_id
      return step1Can
    case 2:
      const step2Can = formData.github_events.length > 0
      return step2Can
    case 3:
      return step3Valid.value
    case 4:
      // Step 4: Need either template or inline prompt with at least one field
      const hasPromptOrInline = formData.prompt_id ||
        (formData.inline_prompt_name && formData.inline_prompt_field_definitions.length > 0)
      return hasPromptOrInline
    default:
      return false
  }
})

const canCreate = computed(() => {
  // By step 5, we should always have a prompt_id since step 4 creates it
  return formData.name &&
         formData.repository_id &&
         formData.prompt_id &&
         formData.github_events.length > 0
})

// Validation rules
const requiredValidator = value => !!value || 'This field is required'

// Lifecycle
onMounted(() => {
  if (props.modelValue) {
    loadData()
  }
})

// Watch for dialog opening
watch(() => props.modelValue, (newValue) => {
  if (newValue && (repositories.value.length === 0 || prompts.value.length === 0)) {
    loadData()
  }
})

// Watch for template selection changes
watch(() => formData.prompt_id, (newValue) => {
  if (newValue) {
    // Clear inline prompt fields when template is selected
    formData.inline_prompt_name = ''
    formData.inline_prompt_description = ''
    formData.inline_prompt_content = ''
    formData.inline_prompt_field_definitions = []
  }
})

// Methods
const loadData = async () => {
  await Promise.all([
    loadRepositories(),
    loadPrompts(),
    // TODO: Add users and roles endpoints later
    // loadUsers(),
    // loadRoles(),
  ])
}

const loadRepositories = async () => {
  try {
    loadingRepositories.value = true
    const response = await $api($endpoint('GET_REPOSITORIES'))
    if (response.success) {
      repositories.value = response.data
    }
  } catch (error) {
    console.error('Error loading repositories:', error)
    snackbar.show('Failed to load repositories', 'error')
  } finally {
    loadingRepositories.value = false
  }
}

const loadPrompts = async () => {
  try {
    loadingPrompts.value = true
    const response = await $api($endpoint('INDEX_PROMPT_TEMPLATES'))
    if (response.success) {
      prompts.value = response.data
    }
  } catch (error) {
    console.error('Error loading prompt templates:', error)
    snackbar.show('Failed to load prompt templates', 'error')
  } finally {
    loadingPrompts.value = false
  }
}

// TODO: Implement loadUsers and loadRoles when endpoints are available
// const loadUsers = async () => { ... }
// const loadRoles = async () => { ... }

const nextStep = async () => {
  
  // Validate current step
  let valid = true

  if (currentStep.value === 1 && step1Form.value) {
    const result = await step1Form.value.validate()
    valid = result.valid
  } else if (currentStep.value === 2) {
    valid = formData.github_events.length > 0
    if (!valid) {
      snackbar.show('Please select at least one GitHub event', 'error')
      return
    }
    // Update event filters
    updateEventFilters()
  } else if (currentStep.value === 3 && step3Form.value) {
    const result = await step3Form.value.validate()
    valid = result.valid
  } else if (currentStep.value === 4) {
    // Check if we have a template selected (need to convert to prompt)
    const hasTemplate = formData.prompt_id && !formData.inline_prompt_name
    // Check if we have inline prompt data
    const hasInlinePrompt = !formData.prompt_id && formData.inline_prompt_name && formData.inline_prompt_field_definitions.length > 0
    
    if (hasTemplate) {
      // User selected a template - need to create a prompt from it
      
      try {
        isLoading.value = true
        
        // First, get the template data
        const templateResponse = await $api($endpoint('SHOW_PROMPT_TEMPLATE', { id: formData.prompt_id }))

        if (!templateResponse.success) {
          snackbar.show('❌ Failed to fetch template data', 'error')
          valid = false
          return
        }

        const template = templateResponse.data
        
        // Now create a prompt using the template's data
        const promptResponse = await $api($endpoint('STORE_PROMPT'), {
          method: 'POST',
          body: {
            name: template.name + ' (from template)',
            description: template.description || 'Created from template',
            field_definitions: template.field_definitions || []
          }
        })


        if (promptResponse.success) {
          // Record template usage
          await $api($endpoint('USE_PROMPT_TEMPLATE', { id: formData.prompt_id }), {
            method: 'POST'
          })
          
          // Clear template ID and set the new prompt ID
          const oldTemplateId = formData.prompt_id
          formData.prompt_id = promptResponse.data.id
          snackbar.show('✅ Prompt created from template! ID: ' + formData.prompt_id, 'success')
        } else {
          snackbar.show('Failed to create prompt from template', 'error')
          valid = false
        }
      } catch (error) {
        snackbar.show('Failed to create prompt from template', 'error')
        valid = false
      } finally {
        isLoading.value = false
      }
    } else if (hasInlinePrompt) {
      try {
        isLoading.value = true
        
        const promptResponse = await $api($endpoint('STORE_PROMPT'), {
          method: 'POST',
          body: {
            name: formData.inline_prompt_name,
            description: formData.inline_prompt_description,
            field_definitions: formData.inline_prompt_field_definitions
          }
        })


        if (promptResponse.success) {
          formData.prompt_id = promptResponse.data.id
          snackbar.show('✅ Prompt created successfully! ID: ' + formData.prompt_id, 'success')
        } else {
          snackbar.show(promptResponse.message || 'Failed to create prompt', 'error')
          valid = false
        }
      } catch (error) {
        snackbar.show(error.response?.data?.message || 'Failed to create prompt', 'error')
        valid = false
      } finally {
        isLoading.value = false
      }
    } else {
      snackbar.show('Please select a template or create a custom prompt', 'error')
      valid = false
    }
  }

  if (valid && currentStep.value < 5) {
    currentStep.value++
  }
}

const previousStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const updateEventFilters = () => {
  const filters = {}

  if (formData.github_events.includes('push') && branchFilters.value.length > 0) {
    filters.branches = branchFilters.value
  }

  if (formData.github_events.includes('pull_request') && prActions.value.length > 0) {
    filters.actions = prActions.value
  }

  formData.event_filters = filters
}

// Field builder methods
const addField = (type) => {
  
  const newField = {
    type,
    name: `field_${Date.now()}`,
    label: '',
    placeholder: '',
    required: false,
  }

  if (type === 'select') {
    newField.options = []
    newField.optionsText = ''
  }

  formData.inline_prompt_field_definitions.push(newField)
  
}

const removeField = (index) => {
  formData.inline_prompt_field_definitions.splice(index, 1)
}

const getFieldTypeLabel = (type) => {
  const labels = {
    text: 'Text Field',
    textarea: 'Text Area',
    select: 'Select'
  }
  return labels[type] || type
}

const getFieldTypeColor = (type) => {
  const colors = {
    text: 'primary',
    textarea: 'secondary',
    select: 'success'
  }
  return colors[type] || 'default'
}

const updateFieldOptions = (field) => {
  if (field.type === 'select') {
    field.options = field.optionsText
      .split('\n')
      .map(option => option.trim())
      .filter(option => option.length > 0)
  }
}

const getTargetingSummary = () => {
  switch (formData.target_type) {
    case 'all':
      return 'All repository users'
    case 'specific_users':
      return `${formData.target_users.length} specific users`
    case 'roles':
      return `${formData.target_roles.length} roles`
    default:
      return 'Unknown'
  }
}

const resetForm = () => {
  // Reset step
  currentStep.value = 1

  // Reset form data
  Object.assign(formData, {
    name: '',
    description: '',
    repository_id: '',
    prompt_id: '',
    inline_prompt_name: '',
    inline_prompt_description: '',
    inline_prompt_content: '',
    inline_prompt_field_definitions: [],
    github_events: ['push'],
    event_filters: {},
    target_type: 'all',
    target_users: [],
    target_roles: [],
    delivery_delay_minutes: 0,
  })

  // Reset additional fields
  branchFilters.value = []
  prActions.value = ['opened', 'closed']

  // Reset state
  errorMessage.value = ''
  successMessage.value = ''
  isLoading.value = false
}

const closeDialog = () => {
  emit('update:modelValue', false)
  setTimeout(resetForm, 300)
}

const handleSubmit = async () => {
  updateEventFilters()

  isLoading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    // Ensure we have a prompt - create one if needed (fallback)
    if (!formData.prompt_id && formData.inline_prompt_name && formData.inline_prompt_field_definitions.length > 0) {
      
      const promptResponse = await $api($endpoint('STORE_PROMPT'), {
        method: 'POST',
        body: {
          name: formData.inline_prompt_name,
          description: formData.inline_prompt_description,
          field_definitions: formData.inline_prompt_field_definitions
        }
      })

      if (promptResponse.success) {
        formData.prompt_id = promptResponse.data.id
        snackbar.show('Prompt created successfully!', 'success')
      } else {
        errorMessage.value = 'Failed to create prompt: ' + (promptResponse.message || 'Unknown error')
        return
      }
    }

    if (!formData.prompt_id) {
      errorMessage.value = 'No prompt available. Please select a template or create a custom prompt.'
      return
    }

    
    const endpoint = isEditMode.value
      ? $endpoint('UPDATE_TRIGGER', { id: props.trigger.id })
      : $endpoint('STORE_TRIGGER')

    const method = isEditMode.value ? 'PUT' : 'POST'

    const response = await $api(endpoint, { method, body: formData })

    if (response.success) {
      successMessage.value = isEditMode.value
        ? 'Trigger updated successfully!'
        : 'Trigger created successfully!'

      emit('saved', response.data)

      setTimeout(() => {
        closeDialog()
      }, 1500)
    } else {
      errorMessage.value = response.message || 'Failed to save trigger'
    }
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
    if (newValue) {
      loadData()

      if (props.trigger) {
        // Populate form with existing trigger data
        Object.assign(formData, {
          name: props.trigger.name || '',
          description: props.trigger.description || '',
          repository_id: props.trigger.repository_id || '',
          prompt_id: props.trigger.prompt_id || '',
          github_events: props.trigger.github_events || ['push'],
          event_filters: props.trigger.event_filters || {},
          target_type: props.trigger.target_type || 'all',
          target_users: props.trigger.target_users || [],
          target_roles: props.trigger.target_roles || [],
          delivery_delay_minutes: props.trigger.delivery_delay_minutes || 0,
        })

        // Set additional fields
        branchFilters.value = props.trigger.event_filters?.branches || []
        prActions.value = props.trigger.event_filters?.actions || ['opened', 'closed']
      }
    } else {
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
