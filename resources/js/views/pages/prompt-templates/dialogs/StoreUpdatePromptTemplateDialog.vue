<template>
  <VDialog
    :model-value="modelValue"
    max-width="900"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="closeDialog" />

    <VCard :title="isEditMode ? 'Edit Template' : 'Create New Template'">
      <VCardText>
        {{ isEditMode
          ? 'Update the template structure and field definitions'
          : 'Design a structured form template for consistent knowledge capture'
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
            <!-- Basic Template Info -->
            <VCol cols="12" md="6">
              <AppTextField
                v-model="formData.name"
                label="Template Name"
                placeholder="e.g., Code Review Checklist"
                hint="Give your template a descriptive name"
                persistent-hint
                :rules="[requiredValidator, nameValidator]"
                prepend-inner-icon="tabler-tag"
                :disabled="isLoading"
                counter
                maxlength="255"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VSwitch
                v-model="formData.is_active"
                label="Active Template"
                hint="Inactive templates won't appear in lists"
                persistent-hint
                :disabled="isLoading"
                color="primary"
              />
            </VCol>

            <VCol cols="12">
              <AppTextarea
                v-model="formData.description"
                label="Description (Optional)"
                placeholder="Describe the purpose and use case of this template..."
                hint="Brief description of when and how to use this template"
                persistent-hint
                prepend-inner-icon="tabler-align-left"
                :disabled="isLoading"
                rows="3"
                auto-grow
                counter
                maxlength="1000"
              />
            </VCol>
          </VRow>
        </VForm>

        <VDivider class="my-6" />

        <!-- Form Builder Section -->
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
                  @click="addField('text')"
                  prepend-icon="tabler-forms"
                >
                  <VListItemTitle>Text Field</VListItemTitle>
                  <VListItemSubtitle>Single or multi-line text input</VListItemSubtitle>
                </VListItem>

                <VListItem
                  @click="addField('checklist')"
                  prepend-icon="tabler-checkbox"
                >
                  <VListItemTitle>Checklist</VListItemTitle>
                  <VListItemSubtitle>Multiple choice selection</VListItemSubtitle>
                </VListItem>

                <VListItem
                  @click="addField('ranking')"
                  prepend-icon="tabler-sort-ascending"
                >
                  <VListItemTitle>Ranking</VListItemTitle>
                  <VListItemSubtitle>Prioritize items by importance</VListItemSubtitle>
                </VListItem>
              </VList>
            </VMenu>
          </div>

          <!-- Field Definitions List -->
          <div v-if="formData.field_definitions.length === 0" class="text-center py-8">
            <VIcon icon="tabler-forms" size="48" color="grey-lighten-1" class="mb-3" />
            <p class="text-body-1 text-medium-emphasis mb-0">No fields defined yet</p>
            <p class="text-body-2 text-medium-emphasis">Add fields to build your template structure</p>
          </div>

          <VCard
            v-for="(field, index) in formData.field_definitions"
            :key="field.id || index"
            variant="outlined"
            class="mb-4"
          >
            <VCardText>
              <div class="d-flex align-center justify-space-between mb-3">
                <div class="d-flex align-center">
                  <VIcon
                    :icon="getFieldIcon(field.type)"
                    :color="getFieldColor(field.type)"
                    class="me-2"
                  />
                  <VChip
                    :color="getFieldColor(field.type)"
                    size="small"
                    variant="tonal"
                  >
                    {{ field.type }}
                  </VChip>
                </div>

                <div>
                  <IconBtn
                    size="small"
                    @click="moveFieldUp(index)"
                    :disabled="index === 0 || isLoading"
                  >
                    <VIcon icon="tabler-arrow-up" />
                  </IconBtn>
                  <IconBtn
                    size="small"
                    @click="moveFieldDown(index)"
                    :disabled="index === formData.field_definitions.length - 1 || isLoading"
                  >
                    <VIcon icon="tabler-arrow-down" />
                  </IconBtn>
                  <IconBtn
                    size="small"
                    color="error"
                    @click="showRemoveFieldConfirm(index)"
                    :disabled="isLoading"
                  >
                    <VIcon icon="tabler-trash" />
                  </IconBtn>
                </div>
              </div>

              <!-- Field Configuration -->
              <VRow>
                <VCol cols="12" md="8">
                  <AppTextField
                    v-model="field.label"
                    label="Field Label"
                    placeholder="Enter the question or prompt..."
                    :disabled="isLoading"
                    density="compact"
                    variant="outlined"
                  />
                </VCol>

                <VCol cols="12" md="4">
                  <VSwitch
                    v-model="field.required"
                    label="Required Field"
                    :disabled="isLoading"
                    density="compact"
                    color="primary"
                  />
                </VCol>

                <VCol v-if="field.type === 'text'" cols="12">
                  <AppTextField
                    v-model="field.placeholder"
                    label="Placeholder Text (Optional)"
                    placeholder="Hint text for users..."
                    :disabled="isLoading"
                    density="compact"
                    variant="outlined"
                  />
                </VCol>

                <!-- Options for checklist and ranking fields -->
                <VCol v-if="['checklist', 'ranking'].includes(field.type)" cols="12">
                  <div class="mb-2">
                    <span class="text-subtitle-2">Options</span>
                  </div>

                  <div
                    v-for="(option, optionIndex) in field.options"
                    :key="optionIndex"
                    class="d-flex align-center mb-2"
                  >
                    <AppTextField
                      v-model="field.options[optionIndex]"
                      :placeholder="`Option ${optionIndex + 1}`"
                      :disabled="isLoading"
                      density="compact"
                      variant="outlined"
                      class="flex-grow-1 me-2"
                    />
                    <IconBtn
                      size="small"
                      color="error"
                      @click="removeOption(index, optionIndex)"
                      :disabled="field.options.length <= 2 || isLoading"
                    >
                      <VIcon icon="tabler-x" />
                    </IconBtn>
                  </div>

                  <VBtn
                    variant="outlined"
                    size="small"
                    @click="addOption(index)"
                    :disabled="isLoading"
                  >
                    <VIcon icon="tabler-plus" start />
                    Add Option
                  </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </div>

        <!-- Template Preview -->
        <VDivider class="my-6" />

        <div>
          <div class="d-flex align-center mb-4">
            <VIcon icon="tabler-eye" class="me-2" />
            <span class="text-h6">Preview</span>
          </div>

          <VCard variant="tonal" class="pa-4">
            <div v-if="formData.field_definitions.length === 0" class="text-center py-4">
              <p class="text-body-2 text-medium-emphasis mb-0">
                Add fields above to see the preview
              </p>
            </div>

            <div v-else>
              <div v-if="formData.name" class="mb-4">
                <h6 class="text-h6 mb-1">{{ formData.name }}</h6>
                <p v-if="formData.description" class="text-body-2 text-medium-emphasis mb-0">
                  {{ formData.description }}
                </p>
              </div>

              <div
                v-for="(field, index) in formData.field_definitions"
                :key="field.id || index"
                class="mb-4"
              >
                <label class="text-body-1 font-weight-medium d-block mb-2">
                  {{ field.label }}
                  <span v-if="field.required" class="text-error">*</span>
                </label>

                <!-- Text field preview -->
                <AppTextField
                  v-if="field.type === 'text'"
                  :placeholder="field.placeholder || 'Enter your response...'"
                  variant="outlined"
                  density="compact"
                  readonly
                />

                <!-- Checklist preview -->
                <div v-else-if="field.type === 'checklist'">
                  <VCheckbox
                    v-for="option in field.options"
                    :key="option"
                    :label="option"
                    density="compact"
                    disabled
                  />
                </div>

                <!-- Ranking preview -->
                <div v-else-if="field.type === 'ranking'" class="ranking-preview">
                  <p class="text-caption mb-2 text-medium-emphasis">
                    Drag items to rank by priority (most important first)
                  </p>
                  <div
                    v-for="(option, optionIndex) in field.options"
                    :key="option"
                    class="ranking-item d-flex align-center mb-2 pa-2"
                  >
                    <VIcon icon="tabler-grip-vertical" class="me-2 text-disabled" />
                    <span class="ranking-number me-2">{{ optionIndex + 1 }}</span>
                    <span>{{ option }}</span>
                  </div>
                </div>
              </div>
            </div>
          </VCard>
        </div>

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
          Cancel
        </VBtn>

        <VBtn
          color="primary"
          :loading="isLoading"
          :disabled="!canSubmit"
          @click="handleSubmit"
        >
          <VIcon icon="tabler-device-floppy" start />
          {{ isEditMode ? 'Update Template' : 'Create Template' }}
        </VBtn>
      </VCardText>
    </VCard>

    <!-- Remove Field Confirmation Dialog -->
    <ConfirmDialog
      :is-dialog-visible="removeFieldDialog.show"
      :question="removeFieldDialog.question"
      confirm-label="Remove"
      cancel-label="Cancel"
      confirm-color="error"
      icon="tabler-trash"
      color="error"
      @confirm="confirmRemoveField"
      @close="removeFieldDialog.show = false"
    />
  </VDialog>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import DialogCloseBtn from '@/@core/components/DialogCloseBtn.vue'
import ConfirmDialog from '@/components/dialogs/ConfirmDialog.vue'

// Props
const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  template: {
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

// Remove field dialog state
const removeFieldDialog = reactive({
  show: false,
  question: '',
  fieldIndex: null,
})

// Form data
const formData = reactive({
  name: '',
  description: '',
  field_definitions: [],
  is_active: true,
})

// Computed
const isEditMode = computed(() => !!props.template?.id)

const canSubmit = computed(() => {
  // Basic requirements: not loading and has a valid name
  if (isLoading.value || formData.name.length < 3) {
    return false
  }

  // If fields are defined, validate them properly
  if (formData.field_definitions.length > 0) {
    return formData.field_definitions.every(field =>
      field.label &&
      field.label.trim().length > 0 &&
      field.type &&
      (field.type === 'text' || (field.options && field.options.length >= 2))
    )
  }

  // Allow creating template without fields initially (will validate on submit)
  return true
})

// Helper functions
const generateId = () => Date.now().toString()

const getFieldIcon = (type) => {
  const icons = {
    text: 'tabler-forms',
    checklist: 'tabler-checkbox',
    ranking: 'tabler-sort-ascending'
  }
  return icons[type] || 'tabler-forms'
}

const getFieldColor = (type) => {
  const colors = {
    text: 'primary',
    checklist: 'success',
    ranking: 'warning'
  }
  return colors[type] || 'primary'
}

// Validation rules
const requiredValidator = value => !!value || 'This field is required'

const nameValidator = value => {
  if (!value) return true
  if (value.length < 3) return 'Template name must be at least 3 characters'
  if (value.length > 255) return 'Template name must be less than 255 characters'

  return true
}

// Form builder methods
const addField = (type) => {
  const newField = {
    id: generateId(),
    type,
    label: '',
    required: false,
    placeholder: type === 'text' ? '' : undefined,
  }

  // Add default options for checklist and ranking fields
  if (type === 'checklist' || type === 'ranking') {
    newField.options = ['Option 1', 'Option 2']
  }

  formData.field_definitions.push(newField)
}

const showRemoveFieldConfirm = (index) => {
  const field = formData.field_definitions[index]
  removeFieldDialog.fieldIndex = index
  removeFieldDialog.question = `Are you sure you want to remove the field "${field.label || 'Untitled Field'}"?`
  removeFieldDialog.show = true
}

const confirmRemoveField = () => {
  if (removeFieldDialog.fieldIndex !== null) {
    formData.field_definitions.splice(removeFieldDialog.fieldIndex, 1)
  }
  removeFieldDialog.show = false
  removeFieldDialog.fieldIndex = null
}

const moveFieldUp = (index) => {
  if (index > 0) {
    const field = formData.field_definitions.splice(index, 1)[0]
    formData.field_definitions.splice(index - 1, 0, field)
  }
}

const moveFieldDown = (index) => {
  if (index < formData.field_definitions.length - 1) {
    const field = formData.field_definitions.splice(index, 1)[0]
    formData.field_definitions.splice(index + 1, 0, field)
  }
}

const addOption = (fieldIndex) => {
  const field = formData.field_definitions[fieldIndex]
  if (field.options) {
    field.options.push(`Option ${field.options.length + 1}`)
  }
}

const removeOption = (fieldIndex, optionIndex) => {
  const field = formData.field_definitions[fieldIndex]
  if (field.options && field.options.length > 2) {
    field.options.splice(optionIndex, 1)
  }
}

// Dialog methods
const resetForm = () => {
  Object.assign(formData, {
    name: '',
    description: '',
    field_definitions: [],
    is_active: true,
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
  if (!canSubmit.value) return

  // Additional validation before submit
  if (formData.field_definitions.length === 0) {
    errorMessage.value = 'Please add at least one field to your template'
    return
  }

  isLoading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const payload = {
      name: formData.name,
      description: formData.description || null,
      field_definitions: formData.field_definitions,
      is_active: formData.is_active,
    }

    let response
    if (isEditMode.value) {
      response = await $api($endpoint('UPDATE_PROMPT_TEMPLATE', { id: props.template.id }), {
        method: 'PUT',
        body: payload
      })
    } else {
      response = await $api($endpoint('STORE_PROMPT_TEMPLATE'), {
        method: 'POST',
        body: payload
      })
    }

    successMessage.value = isEditMode.value
      ? 'Template updated successfully!'
      : 'Template created successfully!'

    // Emit the saved event
    emit('saved', response.data)

    // Close dialog after a short delay to show success message
    setTimeout(() => {
      closeDialog()
    }, 1500)
  } catch (error) {
    console.error('Error saving template:', error)

    if (error.response?.status === 422) {
      // Validation errors
      const errors = error.response?.data?.errors
      if (errors?.name) {
        errorMessage.value = errors.name[0]
      } else if (errors?.field_definitions) {
        errorMessage.value = 'Please check your field definitions for errors'
      } else {
        errorMessage.value = error.response?.data?.message || 'Please check your input and try again'
      }
    } else {
      errorMessage.value = error.response?.data?.message || 'Failed to save template. Please try again.'
    }
  } finally {
    isLoading.value = false
  }
}

// Watchers
watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue && props.template) {
      // Populate form with existing template data
      Object.assign(formData, {
        name: props.template.name || '',
        description: props.template.description || '',
        field_definitions: props.template.field_definitions ?
          JSON.parse(JSON.stringify(props.template.field_definitions)) : [],
        is_active: props.template.is_active ?? true,
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

.ranking-preview {
  border: 1px solid rgba(var(--v-border-color), 0.12);
  border-radius: 4px;
  padding: 8px;
}

.ranking-item {
  background: rgba(var(--v-theme-surface), 0.04);
  border: 1px solid rgba(var(--v-border-color), 0.08);
  border-radius: 4px;
  cursor: grab;
  transition: all 0.2s;
}

.ranking-item:hover {
  background: rgba(var(--v-theme-primary), 0.08);
  border-color: rgba(var(--v-theme-primary), 0.2);
}

.ranking-number {
  background: rgba(var(--v-theme-primary), 0.1);
  color: rgb(var(--v-theme-primary));
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 600;
}

.field-builder-section {
  max-height: 60vh;
  overflow-y: auto;
}

/* Ensure proper spacing for form builder */
.v-row .v-col {
  padding-top: 8px;
  padding-bottom: 8px;
}
</style>
