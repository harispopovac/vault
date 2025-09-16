<template>
    <VCard>
        <VCardText class="d-flex align-center justify-space-between">
            <h4 class="text-h4">Demo Users</h4>

            <div class="d-flex align-center gap-4">
                <VBtn
                    prepend-icon="tabler-refresh"
                    :loading="loading"
                    @click="loadUsers"
                >
                    Refresh
                </VBtn>
            </div>
        </VCardText>

        <VDivider />

        <VDataTable
            :headers="headers"
            :items="users"
            :loading="loading"
            no-data-text="No users found"
            class="text-no-wrap"
        >
            <!-- User Name Column -->
            <template #item.name="{ item }">
                <div class="d-flex align-center gap-3">
                    <VAvatar size="32" color="primary" variant="tonal">
                        <span class="text-sm font-weight-medium">
                            {{ getUserInitials(item.name) }}
                        </span>
                    </VAvatar>
                    <div>
                        <h6 class="text-h6">{{ item.name }}</h6>
                    </div>
                </div>
            </template>

            <!-- Email Column -->
            <template #item.email="{ item }">
                <span class="text-body-2">{{ item.email }}</span>
            </template>

            <!-- Created Date Column -->
            <template #item.created_at="{ item }">
                <VChip color="primary" variant="tonal" size="small">
                    {{ formatDate(item.created_at) }}
                </VChip>
            </template>

            <!-- Actions Column -->
            <template #item.actions="{ item }">
                <VBtn icon size="small" variant="text">
                    <VIcon icon="tabler-dots-vertical" size="20" />
                    <VMenu activator="parent">
                        <VList>
                            <VListItem @click="viewUser(item)">
                                <template #prepend>
                                    <VIcon icon="tabler-eye" size="16" />
                                </template>
                                <VListItemTitle>View</VListItemTitle>
                            </VListItem>
                            <VListItem @click="editUser(item)">
                                <template #prepend>
                                    <VIcon icon="tabler-edit" size="16" />
                                </template>
                                <VListItemTitle>Edit</VListItemTitle>
                            </VListItem>
                        </VList>
                    </VMenu>
                </VBtn>
            </template>

            <!-- Loading State -->
            <template #loading>
                <div class="text-center pa-6">
                    <VProgressCircular indeterminate color="primary" />
                    <p class="mt-2 mb-0">Loading users...</p>
                </div>
            </template>

            <!-- No Data State -->
            <template #no-data>
                <div class="text-center pa-8">
                    <VIcon
                        icon="tabler-users"
                        size="48"
                        color="disabled"
                        class="mb-2"
                    />
                    <h6 class="text-h6 mb-2">No Users Found</h6>
                    <p class="text-body-2 mb-0">
                        There are no users to display
                    </p>
                </div>
            </template>
        </VDataTable>

        <!-- Error State -->
        <VAlert
            v-if="error"
            type="error"
            class="ma-4"
            closable
            @click:close="error = null"
        >
            {{ error }}
        </VAlert>
    </VCard>
</template>

<script setup>
import { onMounted, ref } from "vue"
import { fetchUsers } from "../services/demoService.js"

// Table headers
const headers = [
    {
        title: "ID",
        key: "id",
        sortable: true,
        width: "80px",
    },
    {
        title: "User",
        key: "name",
        sortable: true,
    },
    {
        title: "Email",
        key: "email",
        sortable: true,
    },
    {
        title: "Created Date",
        key: "created_at",
        sortable: true,
        width: "150px",
    },
    {
        title: "Actions",
        key: "actions",
        sortable: false,
        width: "80px",
    },
]

// Reactive data
const users = ref([])
const loading = ref(false)
const error = ref(null)

// Load users function
const loadUsers = async () => {
    loading.value = true
    error.value = null

    try {
        const response = await fetchUsers()

        users.value = response.data
    } catch (err) {
        error.value = "Failed to load users. Please try again."
        console.error("Error loading users:", err)
    } finally {
        loading.value = false
    }
}

// Helper functions
const formatDate = dateString => {
    return new Date(dateString).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    })
}

const getUserInitials = name => {
    if (!name) return "??"
    
    return name
        .split(" ")
        .map(word => word.charAt(0))
        .join("")
        .toUpperCase()
        .substring(0, 2)
}

// Action functions (placeholder for demo)
const viewUser = user => {
    console.log("View user:", user)

    // You can implement actual user view logic here
}

const editUser = user => {
    console.log("Edit user:", user)

    // You can implement actual user edit logic here
}

// Initialize
onMounted(() => {
    loadUsers()
})
</script>

<style scoped>
.text-no-wrap {
    white-space: nowrap;
}
</style>
