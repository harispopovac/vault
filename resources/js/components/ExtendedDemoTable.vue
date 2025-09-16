<template>
    <!-- Example 1: Using Slots for Custom Content -->
    <DemoUsersTable
        :custom-headers="customHeaders"
        @user-action="handleUserAction"
    >
        <!-- Custom title -->
        <template #title>
            <div class="d-flex align-center gap-2">
                <VIcon icon="tabler-users" />
                <span>Extended User Management</span>
                <VChip color="success" size="small">Project Specific</VChip>
            </div>
        </template>

        <!-- Custom actions in header -->
        <template #actions="{ loading, users, reload }">
            <VBtn
                prepend-icon="tabler-plus"
                :disabled="loading"
                @click="openAddUserDialog"
            >
                Add User
            </VBtn>

            <VBtn
                prepend-icon="tabler-download"
                variant="outlined"
                :disabled="loading"
                @click="exportUsers(users)"
            >
                Export
            </VBtn>

            <VBtn
                prepend-icon="tabler-refresh"
                variant="tonal"
                :loading="loading"
                @click="reload"
            >
                Refresh
            </VBtn>
        </template>

        <!-- Custom row actions -->
        <template #row-actions="{ item, viewUser, editUser }">
            <div class="d-flex gap-1">
                <VBtn icon size="small" variant="text" @click="viewUser(item)">
                    <VIcon icon="tabler-eye" size="16" />
                </VBtn>

                <VBtn icon size="small" variant="text" @click="editUser(item)">
                    <VIcon icon="tabler-edit" size="16" />
                </VBtn>

                <!-- Project-specific: Send email action -->
                <VBtn
                    icon
                    size="small"
                    variant="text"
                    color="info"
                    @click="sendEmail(item)"
                >
                    <VIcon icon="tabler-mail" size="16" />
                </VBtn>

                <!-- Project-specific: Delete action -->
                <VBtn
                    icon
                    size="small"
                    variant="text"
                    color="error"
                    @click="deleteUser(item)"
                >
                    <VIcon icon="tabler-trash" size="16" />
                </VBtn>
            </div>
        </template>
    </DemoUsersTable>

    <!-- Project-specific dialogs -->
    <AddUserDialog v-model="showAddDialog" @user-added="handleUserAdded" />
</template>

<script setup>
import { DemoUsersTable } from "demo-module-frontend"
import { ref } from "vue"
import { useRouter } from "vue-router"
import AddUserDialog from "./dialogs/AddUserDialog.vue"

const router = useRouter()

// Custom headers with project-specific columns
const customHeaders = [
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
        title: "Role", // Project-specific column
        key: "role",
        sortable: true,
    },
    {
        title: "Department", // Project-specific column
        key: "department",
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
        width: "200px",
    },
]

const showAddDialog = ref(false)

// Project-specific handlers
const handleUserAction = ({ action, user }) => {
    console.log(`Handle ${action} for user:`, user)

    switch (action) {
    case "view":
        // Navigate to user profile
        router.push(`/users/${user.id}`)
        break
    case "edit":
        // Open edit dialog
        openEditUserDialog(user)
        break
    }
}

const openAddUserDialog = () => {
    showAddDialog.value = true
}

const exportUsers = users => {
    // Project-specific: Export functionality
    console.log("Exporting users:", users)

    // Implement CSV/Excel export
}

const sendEmail = user => {
    // Project-specific: Email functionality
    console.log("Send email to:", user)

    // Implement email sending
}

const deleteUser = user => {
    // Project-specific: Delete functionality
    console.log("Delete user:", user)

    // Implement user deletion with confirmation
}

const handleUserAdded = newUser => {
    // Log the new user
    console.log("User added:", newUser)

    // Note: The table will auto-refresh when the dialog closes and the parent
    // DemoUsersTable component detects the change through its normal data fetching
}
</script>
