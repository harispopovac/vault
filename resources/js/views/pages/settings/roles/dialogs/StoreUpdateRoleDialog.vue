<template>
  <VDialog
    :model-value="modelValue"
    max-width="500"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="closeDialog" />

    <VCard :title="isEditMode ? 'Edit Role' : 'Create Role'">
      <VCardText>
        {{ isEditMode
          ? 'Update role information and permissions'
          : 'Create a new role for Knowledge Vault access control'
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
            <!-- Role Name -->
            <VCol cols="12">
              <AppTextField
                v-model="formData.name"
                label="Role Name"
                placeholder="e.g. Senior Developer, Team Lead, Reviewer"
                :rules="[rules.required]"
                required
              />
            </VCol>

            <!-- Role Description -->
            <VCol cols="12">
              <AppTextarea
                v-model="formData.description"
                label="Description"
                placeholder="Describe the role's responsibilities and permissions"
                rows="3"
                :rules="[rules.maxLength(500)]"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <!-- Actions -->
      <VCardText class="d-flex justify-end gap-3 pt-0">
        <VBtn
          variant="tonal"
          color="secondary"
          @click="closeDialog"
        >
          Cancel
        </VBtn>
        <VBtn
          :loading="isLoading"
          :disabled="!formData.name?.trim() || isLoading"
          color="primary"
          @click="handleSubmit"
        >
          {{ isEditMode ? 'Update Role' : 'Create Role' }}
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { $endpoint } from '@/utils/endpoints'
import { useSnackbarStore } from '@/stores/snackbar'

// Component props and emits
const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  role: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['update:modelValue', 'saved'])

// Dependencies
const snackbar = useSnackbarStore()

// Component state
const formRef = ref()
const isFormValid = ref(false)
const isLoading = ref(false)

// Form data
const formData = ref({
  name: '',
  description: '',
})

// Validation rules
const rules = {
  required: value => !!value || 'This field is required',
  maxLength: (max) => (value) => !value || value.length <= max || `Maximum ${max} characters allowed`,
}

// Computed
const isEditMode = computed(() => !!props.role?.id)

// Methods
const resetForm = () => {
  formData.value = {
    name: '',
    description: '',
  }
  if (formRef.value) {
    formRef.value.resetValidation()
  }
}

// Watchers
watch(() => props.role, (newRole) => {
  if (newRole) {
    formData.value = {
      name: newRole.name || '',
      description: newRole.description || '',
    }
  } else {
    resetForm()
  }
}, { immediate: true, deep: true })

watch(() => props.modelValue, (isVisible) => {
  if (!isVisible) {
    resetForm()
  }
})

const closeDialog = () => {
  emit('update:modelValue', false)
  resetForm()
}

const handleSubmit = async () => {
  if (!formData.name?.trim()) return

  isLoading.value = true

  try {
    const endpoint = isEditMode.value
      ? $endpoint('UPDATE_ROLE', { id: props.role.id })
      : $endpoint('STORE_ROLE')

    const method = isEditMode.value ? 'PUT' : 'POST'

    const response = await $api(endpoint, {
      method,
      body: {
        name: formData.name,
        description: formData.description
      }
    })

    if (response.success) {
      snackbar.show(
        isEditMode.value ? 'Role updated successfully' : 'Role created successfully',
        'success'
      )
      emit('saved', response.data)
      closeDialog()
    } else {
      snackbar.show(response.message || 'Failed to save role', 'error')
    }
  } catch (error) {
    console.error('Error saving role:', error)
    snackbar.show(
      error.response?.data?.message || 'Failed to save role. Please try again.',
      'error'
    )
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
/* Component specific styles if needed */
</style>