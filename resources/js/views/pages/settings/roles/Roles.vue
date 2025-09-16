<template>
  <div>
    <!-- Roles Header -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h5 class="text-h5 mb-1">👥 Roles Management</h5>
        <p class="text-body-2 text-medium-emphasis">
          Create and manage roles for Knowledge Vault access control
        </p>
      </div>

      <VBtn
        color="primary"
        @click="openDialog()"
      >
        <VIcon icon="tabler-plus" start />
        Add Role
      </VBtn>
    </div>

    <!-- Filters -->
    <VRow class="mb-4">
      <VCol cols="12" md="6">
        <AppTextField
          v-model="searchQuery"
          placeholder="Search roles"
          clearable
        />
      </VCol>
    </VRow>

    <!-- Roles Table -->
    <VCard>
      <!-- Empty State -->
      <div v-if="roles.length === 0 && !loading" class="text-center py-12">
        <VIcon
          icon="tabler-users-group"
          size="80"
          color="grey-lighten-1"
          class="mb-4"
        />
        <h5 class="text-h5 text-grey mb-2">No roles created yet</h5>
        <p class="text-body-1 text-grey mb-4">
          Start by creating your first role to manage user permissions
        </p>
        <VBtn
          color="primary"
          @click="openDialog()"
        >
          <VIcon icon="tabler-plus" start />
          Create First Role
        </VBtn>
      </div>

      <!-- Data Table -->
      <VDataTable
        v-else
        :headers="headers"
        :items="filteredRoles"
        :loading="loading"
        item-value="id"
        class="elevation-0"
      >
        <!-- Name Column -->
        <template #item.name="{ item }">
          <div class="d-flex align-center py-2">
            <VIcon
              icon="tabler-user-cog"
              color="primary"
              size="20"
              class="me-3"
            />
            <div>
              <div class="font-weight-medium">{{ item.name }}</div>
              <div class="text-caption text-medium-emphasis">{{ item.description || 'No description' }}</div>
            </div>
          </div>
        </template>

        <!-- Users Count Column -->
        <template #item.users_count="{ item }">
          <VChip
            size="small"
            variant="tonal"
            color="info"
          >
            {{ item.collaborators_count || 0 }} users
          </VChip>
        </template>

        <!-- Created At Column -->
        <template #item.created_at="{ item }">
          <span class="text-caption text-medium-emphasis">
            {{ formatDate(item.created_at) }}
          </span>
        </template>

        <!-- Actions Column -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <VBtn
              icon
              variant="text"
              size="small"
              @click="openDialog(item)"
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
    </VCard>

    <!-- Store/Update Dialog -->
    <StoreUpdateRoleDialog
      v-model="isDialogVisible"
      :role="selectedRole"
      @saved="handleRoleSaved"
    />

    <!-- Confirm Delete Dialog -->
    <ConfirmDialog
      v-model="isConfirmDialogVisible"
      :is-dialog-visible="isConfirmDialogVisible"
      :question="`Are you sure you want to delete the role '${roleToDelete?.name}'? This action cannot be undone.`"
      icon="tabler-trash"
      color="error"
      confirm-color="error"
      confirm-label="Delete"
      @confirm="handleDeleteConfirm"
      @close="isConfirmDialogVisible = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { $endpoint } from '@/utils/endpoints'
import { useSnackbarStore } from '@/stores/snackbar'
import StoreUpdateRoleDialog from './dialogs/StoreUpdateRoleDialog.vue'
import ConfirmDialog from '@/components/dialogs/ConfirmDialog.vue'

// Dependencies
const snackbar = useSnackbarStore()

// Component state
const roles = ref([])
const loading = ref(false)
const searchQuery = ref('')
const isDialogVisible = ref(false)
const selectedRole = ref(null)

// Delete confirmation state
const isConfirmDialogVisible = ref(false)
const roleToDelete = ref(null)

// Table headers
const headers = [
  { title: 'Role', key: 'name', sortable: true },
  { title: 'Users', key: 'users_count', sortable: true },
  { title: 'Created', key: 'created_at', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' },
]

// Computed
const filteredRoles = computed(() => {
  if (!searchQuery.value) return roles.value

  const query = searchQuery.value.toLowerCase()
  return roles.value.filter(role =>
    role.name.toLowerCase().includes(query) ||
    (role.description && role.description.toLowerCase().includes(query))
  )
})

// Lifecycle
onMounted(() => {
  loadRoles()
})

// Methods
const loadRoles = async () => {
  loading.value = true
  try {
    const response = await $api($endpoint('INDEX_ROLES'))
    if (response.success) {
      roles.value = response.data
    } else {
      snackbar.show('Failed to load roles', 'error')
    }
  } catch (error) {
    console.error('Error loading roles:', error)
    snackbar.show('Failed to load roles', 'error')
  } finally {
    loading.value = false
  }
}

const openDialog = (role = null) => {
  selectedRole.value = role ? { ...role } : null
  isDialogVisible.value = true
}

const handleRoleSaved = (role) => {
  if (selectedRole.value?.id) {
    // Update existing role
    const index = roles.value.findIndex(r => r.id === selectedRole.value.id)
    if (index !== -1) {
      roles.value[index] = role
    }
    snackbar.show('Role updated successfully', 'success')
  } else {
    // Add new role
    roles.value.push(role)
    snackbar.show('Role created successfully', 'success')
  }

  selectedRole.value = null
  isDialogVisible.value = false
}

const confirmDelete = (role) => {
  roleToDelete.value = role
  isConfirmDialogVisible.value = true
}

const handleDeleteConfirm = async () => {
  if (!roleToDelete.value) return

  try {
    const response = await $api($endpoint('DELETE_ROLE', { id: roleToDelete.value.id }), {
      method: 'DELETE'
    })

    if (response.success) {
      roles.value = roles.value.filter(r => r.id !== roleToDelete.value.id)
      snackbar.show('Role deleted successfully', 'success')
    } else {
      snackbar.show('Failed to delete role', 'error')
    }
  } catch (error) {
    console.error('Error deleting role:', error)
    snackbar.show('Failed to delete role', 'error')
  } finally {
    isConfirmDialogVisible.value = false
    roleToDelete.value = null
  }
}

// Helper functions
const formatDate = (dateString) => {
  if (!dateString) return 'N/A'

  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}
</script>

<style scoped>
/* Component specific styles if needed */
</style>