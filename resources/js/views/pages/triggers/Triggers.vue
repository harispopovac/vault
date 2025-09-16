<template>
  <VContainer fluid>
    <!-- Header Section -->
    <VRow>
      <VCol cols="12">
        <VCard>
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <h4 class="text-h4 mb-1">🔔 Triggers</h4>
              <p class="text-body-2 mb-0 text-medium-emphasis">
                Manage automated knowledge capture for your GitHub repositories
              </p>
            </div>

            <VBtn
              color="primary"
              @click="isDialogVisible = true"
            >
              <VIcon icon="tabler-plus" start />
              Add Trigger
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Triggers List Section -->
    <VRow class="mt-6">
      <VCol cols="12">
        <VCard>
          <VCardTitle class="d-flex align-center">
            <VIcon icon="tabler-git-branch" class="me-2" />
            Repository Triggers
          </VCardTitle>
          
          <VCardText>
            <!-- Empty State -->
            <div v-if="triggers.length === 0" class="text-center py-12">
              <VIcon 
                icon="tabler-trigger" 
                size="80" 
                color="grey-lighten-1"
                class="mb-4"
              />
              <h5 class="text-h5 text-grey mb-2">No triggers configured</h5>
              <p class="text-body-1 text-grey mb-4">
                Start by adding your first repository trigger to automatically capture knowledge from your codebase
              </p>
              <VBtn
                color="primary"
                @click="isDialogVisible = true"
              >
                <VIcon icon="tabler-plus" start />
                Add First Trigger
              </VBtn>
            </div>

            <!-- Triggers Data Table -->
            <VDataTable
              v-else
              :headers="headers"
              :items="triggers"
              :loading="loading"
              item-value="id"
              class="elevation-0"
            >
              <template #item.name="{ item }">
                <div class="d-flex align-center">
                  <VAvatar 
                    size="32" 
                    :color="item.is_active ? 'success' : 'grey'"
                    variant="tonal"
                    class="me-3"
                  >
                    <VIcon :icon="item.is_active ? 'tabler-check' : 'tabler-pause'" size="16" />
                  </VAvatar>
                  <div>
                    <div class="font-weight-medium">{{ item.name }}</div>
                    <div class="text-caption text-grey">{{ item.description || 'No description' }}</div>
                  </div>
                </div>
              </template>

              <template #item.repository="{ item }">
                <div class="d-flex align-center">
                  <VIcon icon="tabler-git-branch" class="me-2" />
                  {{ item.repository?.name || 'Unknown Repository' }}
                </div>
              </template>

              <template #item.prompt="{ item }">
                <VChip 
                  size="small" 
                  variant="tonal"
                  color="primary"
                >
                  {{ item.prompt?.name || 'Unknown Prompt' }}
                </VChip>
              </template>

              <template #item.events="{ item }">
                <div class="d-flex flex-wrap gap-1">
                  <VChip
                    v-for="event in item.github_events"
                    :key="event"
                    size="x-small"
                    :color="getEventBadgeColor([event])"
                    variant="tonal"
                  >
                    {{ event }}
                  </VChip>
                </div>
              </template>

              <template #item.targeting="{ item }">
                <span class="text-caption">
                  {{ getTargetTypeText(item.target_type, item.target_users, item.target_roles) }}
                </span>
              </template>

              <template #item.status="{ item }">
                <VChip
                  :color="item.is_active ? 'success' : 'error'"
                  :text="item.is_active ? 'Active' : 'Inactive'"
                  size="small"
                  variant="tonal"
                />
              </template>

              <template #item.actions="{ item }">
                <div class="d-flex gap-1">
                  <VBtn
                    icon
                    variant="text"
                    size="small"
                    :color="item.is_active ? 'warning' : 'success'"
                    @click="toggleTrigger(item)"
                  >
                    <VIcon :icon="item.is_active ? 'tabler-pause' : 'tabler-play'" />
                    <VTooltip activator="parent" location="top">
                      {{ item.is_active ? 'Deactivate' : 'Activate' }}
                    </VTooltip>
                  </VBtn>
                  <VBtn
                    icon
                    variant="text"
                    size="small"
                    @click="editTrigger(item)"
                  >
                    <VIcon icon="tabler-edit" />
                    <VTooltip activator="parent" location="top">
                      Edit
                    </VTooltip>
                  </VBtn>
                  <VBtn
                    icon
                    variant="text"
                    size="small"
                    color="error"
                    @click="confirmDelete(item)"
                  >
                    <VIcon icon="tabler-trash" />
                    <VTooltip activator="parent" location="top">
                      Delete
                    </VTooltip>
                  </VBtn>
                </div>
              </template>
            </VDataTable>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Store/Update Dialog -->
    <StoreUpdateTriggerDialog
      v-model="isDialogVisible"
      :trigger="selectedTrigger"
      @saved="handleTriggerSaved"
    />

    <!-- Confirm Delete Dialog -->
    <ConfirmDialog
      v-model="isConfirmDialogVisible"
      :is-dialog-visible="isConfirmDialogVisible"
      :question="`Are you sure you want to delete '${triggerToDelete?.name}'? This will also remove the GitHub webhook.`"
      icon="tabler-trash"
      color="error"
      confirm-color="error"
      confirm-label="Delete"
      @confirm="handleDeleteConfirm"
      @close="isConfirmDialogVisible = false"
    />
  </VContainer>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { $api } from '@/utils/api'
import { $endpoint } from '@/utils/endpoints'
import { useSnackbarStore } from '@/stores/snackbar'
import StoreUpdateTriggerDialog from './dialogs/StoreUpdateTriggerDialog.vue'
import ConfirmDialog from '@/components/dialogs/ConfirmDialog.vue'

// Component state
const isDialogVisible = ref(false)
const selectedTrigger = ref(null)
const triggers = ref([])
const loading = ref(false)

// Delete confirmation state
const isConfirmDialogVisible = ref(false)
const triggerToDelete = ref(null)

// Data table headers
const headers = [
  { title: 'Trigger', key: 'name', sortable: true },
  { title: 'Repository', key: 'repository', sortable: false },
  { title: 'Prompt', key: 'prompt', sortable: false },
  { title: 'Events', key: 'events', sortable: false },
  { title: 'Targeting', key: 'targeting', sortable: false },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, align: 'center' },
]

// Composables
const snackbar = useSnackbarStore()

// Lifecycle
onMounted(() => {
  loadTriggers()
})

// Methods
const loadTriggers = async () => {
  try {
    loading.value = true
    const response = await $api($endpoint('INDEX_TRIGGERS'))
    
    if (response.success) {
      triggers.value = response.data
    } else {
      snackbar.show('Failed to load triggers', 'error')
    }
  } catch (error) {
    console.error('Error loading triggers:', error)
    snackbar.show('Failed to load triggers', 'error')
  } finally {
    loading.value = false
  }
}

const editTrigger = (trigger) => {
  selectedTrigger.value = { ...trigger }
  isDialogVisible.value = true
}

const confirmDelete = (trigger) => {
  triggerToDelete.value = trigger
  isConfirmDialogVisible.value = true
}

const handleDeleteConfirm = async () => {
  if (!triggerToDelete.value) return
  
  try {
    const response = await $api($endpoint('DELETE_TRIGGER', { id: triggerToDelete.value.id }), { method: 'DELETE' })
    
    if (response.success) {
      triggers.value = triggers.value.filter(t => t.id !== triggerToDelete.value.id)
      snackbar.show('Trigger deleted successfully', 'success')
    } else {
      snackbar.show('Failed to delete trigger', 'error')
    }
  } catch (error) {
    console.error('Error deleting trigger:', error)
    snackbar.show('Failed to delete trigger', 'error')
  } finally {
    isConfirmDialogVisible.value = false
    triggerToDelete.value = null
  }
}

const toggleTrigger = async (trigger) => {
  try {
    const response = await $api($endpoint('TOGGLE_TRIGGER', { id: trigger.id }), { method: 'POST' })
    
    if (response.success) {
      trigger.is_active = response.data.is_active
      snackbar.show(trigger.is_active ? 'Trigger activated' : 'Trigger deactivated', 'success')
    } else {
      snackbar.show('Failed to toggle trigger', 'error')
    }
  } catch (error) {
    console.error('Error toggling trigger:', error)
    snackbar.show('Failed to toggle trigger', 'error')
  }
}

const handleTriggerSaved = (trigger) => {
  // If editing, update the existing trigger
  if (selectedTrigger.value && selectedTrigger.value.id) {
    const index = triggers.value.findIndex(t => t.id === selectedTrigger.value.id)
    if (index !== -1) {
      triggers.value[index] = trigger
    }
  } else {
    // Add new trigger
    triggers.value.push(trigger)
  }
  
  // Reset state
  selectedTrigger.value = null
  isDialogVisible.value = false
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now - date)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays < 1) return 'today'
  if (diffDays === 1) return 'yesterday'
  if (diffDays < 7) return `${diffDays} days ago`
  if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`
  
  return date.toLocaleDateString()
}

const getEventBadgeColor = (events) => {
  if (events.includes('push')) return 'primary'
  if (events.includes('pull_request')) return 'success'
  if (events.includes('issues')) return 'warning'
  return 'info'
}

const getTargetTypeText = (targetType, targetUsers, targetRoles) => {
  switch (targetType) {
    case 'all':
      return 'All repository users'
    case 'specific_users':
      return `${targetUsers?.length || 0} specific users`
    case 'roles':
      return `${targetRoles?.length || 0} roles`
    default:
      return 'Unknown'
  }
}
</script>

<style scoped>
.v-container {
  max-width: 1200px;
}
</style>
