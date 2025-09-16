<template>
  <VContainer fluid>
    <!-- Header Section -->
    <VRow>
      <VCol cols="12">
        <VCard>
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <h4 class="text-h4 mb-1">👥 Developers</h4>
              <p class="text-body-2 mb-0 text-medium-emphasis">
                Manage GitHub collaborators and assign Knowledge Vault roles
              </p>
            </div>

            <VBtn
              color="primary"
              :loading="syncing"
              @click="syncAllCollaborators"
            >
              <VIcon icon="tabler-refresh" start />
              Sync Collaborators
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Filters Section -->
    <VRow class="mt-6">
      <VCol cols="12" md="3">
        <AppSelect
          v-model="selectedRepository"
          placeholder="Filter by Repository"
          :items="repositoryOptions"
          item-title="name"
          item-value="id"
          clearable
        />
      </VCol>
      <VCol cols="12" md="3">
        <AppSelect
          v-model="selectedRole"
          placeholder="Filter by Role"
          :items="roleOptions"
          item-title="name"
          item-value="id"
          clearable
        />
      </VCol>
      <VCol cols="12" md="3">
        <AppSelect
          v-model="selectedPermission"
          placeholder="Filter by Permission"
          :items="permissionOptions"
          clearable
        />
      </VCol>
      <VCol cols="12" md="3">
        <AppTextField
          v-model="searchQuery"
          placeholder="Search developers"
          clearable
        />
      </VCol>
    </VRow>

    <!-- Developers Section -->
    <VRow class="mt-6">
      <VCol cols="12">
        <VCard>
          <!-- Empty State -->
          <div v-if="developers.length === 0 && !loading" class="text-center py-12">
            <VIcon
              icon="tabler-users"
              size="80"
              color="grey-lighten-1"
              class="mb-4"
            />
            <h5 class="text-h5 text-grey mb-2">No developers found</h5>
            <p class="text-body-1 text-grey mb-4">
              Start by syncing collaborators from your GitHub repositories
            </p>
            <VBtn
              color="primary"
              :loading="syncing"
              @click="syncAllCollaborators"
            >
              <VIcon icon="tabler-refresh" start />
              Sync Collaborators
            </VBtn>
          </div>

          <!-- Data Table -->
          <VDataTable
            v-else
            :headers="headers"
            :items="filteredDevelopers"
            :loading="loading"
            item-value="github_user_id"
            class="elevation-0"
          >
            <!-- Avatar & Name Column -->
            <template #item.developer="{ item }">
              <div class="d-flex align-center py-2">
                <VAvatar
                  size="40"
                  :color="item.github_avatar_url ? undefined : 'primary'"
                  :variant="item.github_avatar_url ? undefined : 'tonal'"
                  class="me-3"
                >
                  <VImg v-if="item.github_avatar_url" :src="item.github_avatar_url" />
                  <span v-else>{{ getInitials(item.github_username) }}</span>
                </VAvatar>
                <div>
                  <div class="font-weight-medium">{{ item.github_username }}</div>
                  <div class="text-caption text-medium-emphasis">{{ item.github_email || 'No email' }}</div>
                </div>
              </div>
            </template>

            <!-- Repositories Column -->
            <template #item.repositories="{ item }">
              <div class="d-flex flex-wrap gap-1">
                <VChip
                  v-for="repo in item.repositories.slice(0, 2)"
                  :key="repo.id"
                  size="small"
                  variant="tonal"
                  :color="getPermissionColor(repo.permission_level)"
                >
                  {{ repo.name }}
                  <VTooltip activator="parent" location="top">
                    {{ repo.permission_level }} access
                  </VTooltip>
                </VChip>
                <VChip
                  v-if="item.repositories.length > 2"
                  size="small"
                  variant="text"
                  color="grey"
                >
                  +{{ item.repositories.length - 2 }} more
                </VChip>
              </div>
            </template>

            <!-- Role Assignment Column -->
            <template #item.role="{ item }">
              <AppSelect
                :model-value="item.vault_role_id"
                :items="roleOptions"
                item-title="name"
                item-value="id"
                placeholder="No role assigned"
                density="compact"
                style="min-width: 150px"
                @update:model-value="updateDeveloperRole(item, $event)"
              />
            </template>

            <!-- Last Synced Column -->
            <template #item.last_synced_at="{ item }">
              <span class="text-caption text-medium-emphasis">
                {{ formatDate(item.last_synced_at) }}
              </span>
            </template>

            <!-- Actions Column -->
            <template #item.actions="{ item }">
              <VBtn
                icon
                variant="text"
                size="small"
                @click="syncRepositoryCollaborators(item.repositories[0]?.id)"
              >
                <VIcon icon="tabler-refresh" />
                <VTooltip activator="parent" location="top">
                  Sync this developer's repositories
                </VTooltip>
              </VBtn>
            </template>
          </VDataTable>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { $endpoint } from '@/utils/endpoints'
import { useSnackbarStore } from '@/stores/snackbar'

// Dependencies
const snackbar = useSnackbarStore()

// Component state
const developers = ref([])
const repositories = ref([])
const roles = ref([])
const loading = ref(false)
const syncing = ref(false)

// Filter state
const selectedRepository = ref(null)
const selectedRole = ref(null)
const selectedPermission = ref(null)
const searchQuery = ref('')

// Table headers
const headers = [
  { title: 'Developer', key: 'developer', sortable: false },
  { title: 'Repositories', key: 'repositories', sortable: false },
  { title: 'Role', key: 'role', sortable: false },
  { title: 'Last Synced', key: 'last_synced_at', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' },
]

// Permission options
const permissionOptions = [
  { title: 'Read', value: 'read' },
  { title: 'Write', value: 'write' },
  { title: 'Admin', value: 'admin' },
]

// Computed properties
const repositoryOptions = computed(() => {
  return [
    { name: 'All Repositories', id: null },
    ...repositories.value
  ]
})

const roleOptions = computed(() => {
  return [
    { name: 'No Role', id: null },
    ...roles.value
  ]
})

const filteredDevelopers = computed(() => {
  let filtered = [...developers.value]

  // Filter by repository
  if (selectedRepository.value) {
    filtered = filtered.filter(dev =>
      dev.repositories.some(repo => repo.id === selectedRepository.value)
    )
  }

  // Filter by role
  if (selectedRole.value) {
    filtered = filtered.filter(dev => dev.vault_role_id === selectedRole.value)
  }

  // Filter by permission
  if (selectedPermission.value) {
    filtered = filtered.filter(dev =>
      dev.repositories.some(repo => repo.permission_level === selectedPermission.value)
    )
  }

  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(dev =>
      dev.github_username.toLowerCase().includes(query) ||
      (dev.github_email && dev.github_email.toLowerCase().includes(query))
    )
  }

  return filtered
})

// Lifecycle
onMounted(() => {
  loadDevelopers()
  loadRepositories()
  loadRoles()
})

// Methods
const loadDevelopers = async () => {
  loading.value = true
  try {
    const response = await $api($endpoint('INDEX_DEVELOPERS'))
    if (response.success) {
      developers.value = response.data
    } else {
      snackbar.show('Failed to load developers', 'error')
    }
  } catch (error) {
    console.error('Error loading developers:', error)
    snackbar.show('Failed to load developers', 'error')
  } finally {
    loading.value = false
  }
}

const loadRepositories = async () => {
  try {
    const response = await $api($endpoint('GET_DEVELOPER_REPOSITORIES'))
    if (response.success) {
      repositories.value = response.data
    }
  } catch (error) {
    console.error('Error loading repositories:', error)
  }
}

const loadRoles = async () => {
  try {
    const response = await $api($endpoint('GET_DEVELOPER_ROLES'))
    if (response.success) {
      roles.value = response.data
    }
  } catch (error) {
    console.error('Error loading roles:', error)
  }
}

const syncAllCollaborators = async () => {
  syncing.value = true
  try {
    const response = await $api($endpoint('SYNC_DEVELOPERS'), {
      method: 'POST'
    })
    if (response.success) {
      snackbar.show('Collaborators synced successfully', 'success')
      await loadDevelopers()
    } else {
      snackbar.show('Failed to sync collaborators', 'error')
    }
  } catch (error) {
    console.error('Error syncing collaborators:', error)
    snackbar.show('Failed to sync collaborators', 'error')
  } finally {
    syncing.value = false
  }
}

const syncRepositoryCollaborators = async (repositoryId) => {
  if (!repositoryId) return
  
  syncing.value = true
  try {
    const response = await $api($endpoint('SYNC_REPOSITORY_DEVELOPERS', { id: repositoryId }), {
      method: 'POST'
    })
    if (response.success) {
      snackbar.show('Repository collaborators synced successfully', 'success')
      await loadDevelopers()
    } else {
      snackbar.show('Failed to sync repository collaborators', 'error')
    }
  } catch (error) {
    console.error('Error syncing repository collaborators:', error)
    snackbar.show('Failed to sync repository collaborators', 'error')
  } finally {
    syncing.value = false
  }
}

const updateDeveloperRole = async (developer, roleId) => {
  try {
    const response = await $api($endpoint('UPDATE_DEVELOPER_ROLE', { id: developer.id }), {
      method: 'PATCH',
      body: { role_id: roleId }
    })
    if (response.success) {
      // Update local data
      const devIndex = developers.value.findIndex(d => d.github_user_id === developer.github_user_id)
      if (devIndex !== -1) {
        developers.value[devIndex].vault_role_id = roleId
        developers.value[devIndex].role = response.data.role
      }
      snackbar.show('Role updated successfully', 'success')
    } else {
      snackbar.show('Failed to update role', 'error')
    }
  } catch (error) {
    console.error('Error updating role:', error)
    snackbar.show('Failed to update role', 'error')
  }
}

// Helper functions
const getInitials = (username) => {
  return username
    .split(/[\s-_]/)
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const getPermissionColor = (permission) => {
  const permissionMap = {
    admin: 'error',
    write: 'warning',
    read: 'info',
  }
  return permissionMap[permission] || 'default'
}


const formatDate = (dateString) => {
  if (!dateString) return 'Never'
  
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now - date)
  const diffHours = Math.ceil(diffTime / (1000 * 60 * 60))
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffHours < 1) return 'Just now'
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffDays === 1) return 'Yesterday'
  if (diffDays < 7) return `${diffDays}d ago`
  
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined,
  })
}
</script>

<style scoped>
.v-container {
  max-width: 1400px;
}
</style>