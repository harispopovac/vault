<template>
  <VContainer fluid>
    <!-- Header Section -->
    <VRow>
      <VCol cols="12">
        <VCard>
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <h4 class="text-h4 mb-1">📦 Repositories</h4>
              <p class="text-body-2 mb-0 text-medium-emphasis">
                Manage your linked GitHub repositories
              </p>
            </div>

            <VBtn
              color="primary"
              @click="openDialog()"
            >
              <VIcon icon="tabler-plus" start />
              Add Repository
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Search and Filters -->
    <VRow class="mt-6">
      <VCol cols="12" md="4">
        <VTextField
          v-model="searchQuery"
          prepend-inner-icon="tabler-search"
          placeholder="Search repositories..."
          clearable
          hide-details
        />
      </VCol>
      <VCol cols="12" md="8" class="d-flex align-center justify-end gap-2">
        <VChip
          v-for="status in repositoryStatuses"
          :key="status.value"
          :color="selectedStatus === status.value ? status.color : 'default'"
          :variant="selectedStatus === status.value ? 'flat' : 'tonal'"
          clickable
          @click="selectedStatus = selectedStatus === status.value ? null : status.value"
        >
          {{ status.label }}
        </VChip>
      </VCol>
    </VRow>

    <!-- Repositories Grid -->
    <VRow class="mt-6">
      <!-- Empty State -->
      <VCol v-if="filteredRepositories.length === 0" cols="12">
        <VCard>
          <VCardText class="text-center py-12">
            <VIcon
              icon="tabler-git-branch"
              size="80"
              color="grey-lighten-1"
              class="mb-4"
            />
            <h5 class="text-h5 text-grey mb-2">
              {{ searchQuery || selectedStatus ? 'No repositories found' : 'No repositories linked' }}
            </h5>
            <p class="text-body-1 text-grey mb-4">
              {{ searchQuery || selectedStatus
                ? 'Try adjusting your search or filters'
                : 'Link your first GitHub repository to start tracking code changes'
              }}
            </p>
            <VBtn
              v-if="!searchQuery && !selectedStatus"
              color="primary"
              @click="openDialog()"
            >
              <VIcon icon="tabler-plus" start />
              Add New Repository
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Repository Cards -->
      <VCol
        v-for="repository in filteredRepositories"
        :key="repository.id"
        cols="12"
        md="6"
        lg="4"
      >
        <VCard
          hover
          class="h-100 d-flex flex-column"
        >
          <!-- Card Header -->
          <VCardTitle class="d-flex align-center justify-space-between">
            <div class="d-flex align-center">
              <VIcon icon="tabler-git-branch" class="me-2" />
              <span class="text-truncate">{{ repository.name }}</span>
            </div>
            <VChip
              :color="getStatusColor(repository.status)"
              size="small"
              variant="tonal"
            >
              {{ repository.status }}
            </VChip>
          </VCardTitle>

          <!-- Card Content -->
          <VCardText class="flex-grow-1">
            <p class="text-body-2 mb-3">
              {{ repository.description || 'No description available' }}
            </p>

            <!-- Repository Stats -->
            <div class="d-flex gap-3 mb-3">
              <div class="text-center">
                <VIcon icon="tabler-star" size="20" color="warning" />
                <div class="text-caption">{{ repository.stars }}</div>
              </div>
              <div class="text-center">
                <VIcon icon="tabler-git-fork" size="20" color="info" />
                <div class="text-caption">{{ repository.forks }}</div>
              </div>
              <div class="text-center">
                <VIcon icon="tabler-eye" size="20" color="success" />
                <div class="text-caption">{{ repository.watchers }}</div>
              </div>
            </div>

            <!-- Additional Info -->
            <div class="mt-auto">
              <VChip size="x-small" variant="tonal" class="me-2 mb-1">
                <VIcon icon="tabler-code" size="14" start />
                {{ repository.language }}
              </VChip>
              <VChip size="x-small" variant="tonal" class="mb-1">
                <VIcon icon="tabler-calendar" size="14" start />
                {{ formatDate(repository.lastActivity) }}
              </VChip>
            </div>
          </VCardText>

          <VDivider />

          <!-- Card Actions -->
          <VCardActions class="pa-3">
            <VBtn
              variant="text"
              size="small"
              :href="repository.url"
              target="_blank"
            >
              <VIcon icon="tabler-external-link" start />
              View on GitHub
            </VBtn>

            <VSpacer />

            <VBtn
              icon
              variant="text"
              size="small"
              @click="openDialog(repository)"
            >
              <VIcon icon="tabler-settings" />
            </VBtn>

            <VBtn
              icon
              variant="text"
              size="small"
              color="error"
              @click="confirmDelete(repository)"
            >
              <VIcon icon="tabler-unlink" />
            </VBtn>
          </VCardActions>
        </VCard>
      </VCol>
    </VRow>

    <!-- Store Dialog (for new repositories) -->
    <StoreRepositoryDialog
      v-if="!selectedRepository"
      v-model="isDialogVisible"
      @saved="handleRepositorySaved"
    />

    <!-- Update Dialog (for editing repositories) -->
    <StoreUpdateRepositoryDialog
      v-if="selectedRepository"
      v-model="isDialogVisible"
      :repository="selectedRepository"
      @saved="handleRepositorySaved"
    />

    <!-- Confirmation Dialog -->
    <VDialog
      v-model="isConfirmDialogVisible"
      max-width="400"
      persistent
    >
      <VCard>
        <VCardTitle>Confirm Unlink</VCardTitle>
        <VCardText>
          Are you sure you want to unlink <strong>{{ repositoryToDelete?.name }}</strong>? 
          This action cannot be undone and will remove all associated knowledge entries.
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn 
            variant="text" 
            @click="isConfirmDialogVisible = false"
          >
            Cancel
          </VBtn>
          <VBtn 
            color="error" 
            @click="confirmUnlink"
          >
            Unlink
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Snackbar for notifications -->
    <VSnackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="snackbar.timeout"
    >
      {{ snackbar.text }}
    </VSnackbar>
  </VContainer>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import StoreRepositoryDialog from './dialogs/StoreRepositoryDialog.vue'
import StoreUpdateRepositoryDialog from './dialogs/StoreUpdateRepositoryDialog.vue'
import { $api } from '@/utils/api'
import { $endpoint } from '@/utils/endpoints'

// Component state
const isDialogVisible = ref(false)
const selectedRepository = ref(null)
const repositories = ref([])
const searchQuery = ref('')
const selectedStatus = ref(null)
const isConfirmDialogVisible = ref(false)
const repositoryToDelete = ref(null)

// Snackbar state
const snackbar = reactive({
  show: false,
  text: '',
  color: 'success',
  timeout: 3000,
})

// Repository status options
const repositoryStatuses = [
  { label: 'Active', value: 'active', color: 'success' },
  { label: 'Inactive', value: 'inactive', color: 'warning' },
  { label: 'Archived', value: 'archived', color: 'grey' },
]


// Computed
const filteredRepositories = computed(() => {
  let filtered = repositories.value

  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(repo =>
      repo.name.toLowerCase().includes(query) ||
      (repo.description && repo.description.toLowerCase().includes(query))
    )
  }

  // Filter by status
  if (selectedStatus.value) {
    filtered = filtered.filter(repo => repo.status === selectedStatus.value)
  }

  return filtered
})

// Lifecycle
onMounted(() => {
  // Load repositories from API
  loadRepositories()
})

// Methods
const loadRepositories = async () => {
  try {
    const response = await $api($endpoint('GET_REPOSITORIES'))
    repositories.value = response.data || []
  } catch (error) {
    console.error('Failed to load repositories:', error)
    showNotification('Failed to load repositories', 'error')
    repositories.value = []
  }
}

const openDialog = (repository = null) => {
  selectedRepository.value = repository ? { ...repository } : null
  isDialogVisible.value = true
}

const confirmDelete = (repository) => {
  repositoryToDelete.value = repository
  isConfirmDialogVisible.value = true
}

const confirmUnlink = async () => {
  if (!repositoryToDelete.value) return
  
  try {
    await $api($endpoint('DELETE_REPOSITORY', { id: repositoryToDelete.value.id }), {
      method: 'DELETE'
    })

    // Remove from local list
    repositories.value = repositories.value.filter(r => r.id !== repositoryToDelete.value.id)

    // Show notification
    showNotification('Repository unlinked successfully', 'success')
  } catch (error) {
    console.error('Failed to disconnect repository:', error)
    showNotification('Failed to unlink repository', 'error')
  } finally {
    isConfirmDialogVisible.value = false
    repositoryToDelete.value = null
  }
}

const handleRepositorySaved = (repository) => {
  // If editing, update the existing repository
  if (selectedRepository.value && selectedRepository.value.id) {
    const index = repositories.value.findIndex(r => r.id === selectedRepository.value.id)
    if (index !== -1) {
      repositories.value[index] = repository
    }
    showNotification('Repository updated successfully', 'success')
  } else {
    // Add new repository
    repositories.value.push({
      ...repository,
      id: Date.now(), // Temporary ID generation
      status: 'active',
      stars: 0,
      forks: 0,
      watchers: 1,
      lastActivity: new Date().toISOString(),
    })
    showNotification('Repository linked successfully', 'success')
  }

  // Reset state
  selectedRepository.value = null
  isDialogVisible.value = false
}

const getStatusColor = (status) => {
  const statusMap = {
    active: 'success',
    inactive: 'warning',
    archived: 'grey',
  }
  return statusMap[status] || 'default'
}

const formatDate = (dateString) => {
  if (!dateString) return 'Unknown'
  
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now - date)
  const diffMinutes = Math.floor(diffTime / (1000 * 60))
  const diffHours = Math.floor(diffTime / (1000 * 60 * 60))
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffMinutes < 60) return diffMinutes <= 1 ? 'Just now' : `${diffMinutes}m ago`
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffDays === 1) return 'Yesterday'
  if (diffDays < 7) return `${diffDays} days ago`
  if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`
  
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined,
  })
}

const showNotification = (text, color = 'success') => {
  snackbar.text = text
  snackbar.color = color
  snackbar.show = true
}
</script>

<style scoped>
.v-container {
  max-width: 1400px;
}

.v-card {
  transition: transform 0.2s;
}

.v-card:hover {
  transform: translateY(-2px);
}

.text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 200px;
}
</style>
