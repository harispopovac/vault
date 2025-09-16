# Module Extension Guide

This guide explains different patterns for extending feature modules with project-specific functionality while keeping the base modules reusable.

## 🎯 Extension Patterns Overview

### 1. **Slot-Based Extension** ⭐ (Recommended)

Use Vue slots to customize specific parts of components without modifying the base module.

### 2. **Props-Based Configuration**

Pass props to configure behavior and appearance.

### 3. **Event-Driven Extension**

Use emitted events to handle project-specific actions.

### 4. **Controller Inheritance**

Extend backend controllers to add or override functionality.

### 5. **Service Extension**

Create extended API services that build upon base functionality.

---

## 🎨 Frontend Extension Examples

### Slot-Based Extension

```vue
<template>
    <DemoUsersTable @user-action="handleUserAction">
        <!-- Custom title -->
        <template #title>
            <div class="d-flex align-center gap-2">
                <VIcon icon="tabler-users" />
                <span>Extended User Management</span>
                <VChip color="success" size="small">Project Specific</VChip>
            </div>
        </template>

        <!-- Custom header actions -->
        <template #actions="{ loading, users, reload }">
            <VBtn @click="openAddDialog">Add User</VBtn>
            <VBtn @click="exportUsers(users)">Export</VBtn>
            <VBtn @click="reload" :loading="loading">Refresh</VBtn>
        </template>

        <!-- Custom row actions -->
        <template #row-actions="{ item, viewUser, editUser }">
            <VBtn @click="viewUser(item)" icon size="small">
                <VIcon icon="tabler-eye" />
            </VBtn>
            <VBtn @click="sendEmail(item)" icon size="small" color="info">
                <VIcon icon="tabler-mail" />
            </VBtn>
            <VBtn @click="deleteUser(item)" icon size="small" color="error">
                <VIcon icon="tabler-trash" />
            </VBtn>
        </template>
    </DemoUsersTable>
</template>
```

### Props-Based Configuration

```vue
<template>
    <DemoUsersTable
        :custom-headers="projectHeaders"
        :hide-default-actions="false"
        @user-action="handleProjectAction"
    />
</template>

<script setup>
const projectHeaders = [
    { title: "ID", key: "id" },
    { title: "User", key: "name" },
    { title: "Email", key: "email" },
    { title: "Role", key: "role" }, // Project-specific
    { title: "Department", key: "department" }, // Project-specific
    { title: "Actions", key: "actions" },
];
</script>
```

### Event-Driven Extension

```vue
<script setup>
const handleUserAction = ({ action, user }) => {
    switch (action) {
        case "view":
            router.push(`/users/${user.id}`);
            break;
        case "edit":
            openEditDialog(user);
            break;
        // Handle other project-specific actions
    }
};
</script>
```

---

## 🔧 Backend Extension Examples

### Controller Inheritance

```php
<?php
// app/Http/Controllers/ExtendedDemoController.php

class ExtendedDemoController extends DemoController
{
    // Override base method with additional functionality
    public function getUsers()
    {
        $users = User::select('id', 'fname', 'sname', 'email', 'role', 'department', 'created_at')
            ->with(['teams', 'projects']) // Project-specific relationships
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => trim($user->fname . ' ' . $user->sname),
                    'email' => $user->email,
                    'role' => $user->role ?? 'User', // Project-specific
                    'department' => $user->department ?? 'N/A', // Project-specific
                    'teams' => $user->teams ?? [],
                    'projects_count' => $user->projects->count() ?? 0,
                    'created_at' => $user->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $users,
            'meta' => [
                'total' => User::count(),
                'active' => User::whereNotNull('email_verified_at')->count()
            ]
        ]);
    }

    // Add project-specific methods
    public function createUser(Request $request) { /* ... */ }
    public function exportUsers() { /* ... */ }
    public function sendEmailToUser($userId, Request $request) { /* ... */ }
}
```

### Extended Routes

```php
<?php
// routes/demo-extended.php

Route::prefix('api/demo-extended')->group(function () {
    // Override base endpoint
    Route::get('/users', [ExtendedDemoController::class, 'getUsers']);

    // Add project-specific endpoints
    Route::post('/users', [ExtendedDemoController::class, 'createUser']);
    Route::delete('/users/{user}', [ExtendedDemoController::class, 'deleteUser']);
    Route::get('/users/export', [ExtendedDemoController::class, 'exportUsers']);
    Route::post('/users/{user}/send-email', [ExtendedDemoController::class, 'sendEmailToUser']);
});
```

---

## 🌐 Service Extension Examples

### Extended API Service

```javascript
// resources/js/services/extendedDemoService.js

export class ExtendedDemoService {
    // Enhanced base functionality
    static async fetchUsers() {
        const response = await extendedApi.get("/api/demo-extended/users");
        return response.data;
    }

    // Project-specific methods
    static async createUser(userData) {
        const response = await extendedApi.post(
            "/api/demo-extended/users",
            userData,
        );
        return response.data;
    }

    static async exportUsers() {
        const response = await extendedApi.get(
            "/api/demo-extended/users/export",
            {
                responseType: "blob",
            },
        );

        // Handle file download
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement("a");
        link.href = url;
        link.download = `users_export_${new Date().toISOString().split("T")[0]}.csv`;
        link.click();

        return { success: true, message: "Export completed" };
    }

    static async sendEmailToUser(userId, emailData) {
        const response = await extendedApi.post(
            `/api/demo-extended/users/${userId}/send-email`,
            emailData,
        );
        return response.data;
    }
}
```

---

## 🔄 Usage Patterns

### Pattern 1: Complete Override

Replace the base functionality entirely for a specific project.

```vue
<template>
    <!-- Use extended component instead of base -->
    <ExtendedDemoTable />
</template>
```

### Pattern 2: Selective Extension

Use the base component with project-specific slots and props.

```vue
<template>
    <DemoUsersTable :custom-headers="headers">
        <template #actions>
            <!-- Project-specific actions -->
        </template>
    </DemoUsersTable>
</template>
```

### Pattern 3: Composition

Combine base module with additional components.

```vue
<template>
    <div>
        <ProjectStats />
        <DemoUsersTable />
        <ProjectFooter />
    </div>
</template>
```

---

## 📁 File Organization

```
your-project/
├── feature-modules/               # Base modules (reusable)
│   └── demo/
│       ├── frontend/
│       └── backend/
├── app/Http/Controllers/          # Project-specific controllers
│   └── ExtendedDemoController.php
├── resources/js/components/       # Project-specific components
│   └── ExtendedDemoTable.vue
├── resources/js/services/         # Project-specific services
│   └── extendedDemoService.js
└── routes/                        # Project-specific routes
    └── demo-extended.php
```

---

## ✅ Best Practices

### 1. **Keep Base Modules Pure**

-   Never modify base modules for project-specific needs
-   Always extend or override in project files

### 2. **Use Meaningful Naming**

-   `ExtendedDemoController` vs `DemoController`
-   `ProjectSpecificUserTable` vs `UserTable`

### 3. **Document Extensions**

-   Comment why you're extending
-   Document project-specific functionality

### 4. **Version Control**

-   Keep base modules in separate repository if possible
-   Use npm/composer for module distribution

### 5. **Testing**

-   Test both base functionality and extensions
-   Ensure extensions don't break base behavior

---

## 🚀 Advanced Patterns

### Configuration-Based Extension

```javascript
// config/modules.js
export const moduleConfig = {
    demo: {
        features: {
            userExport: true,
            emailSending: true,
            userDeletion: false,
        },
        customFields: ["role", "department"],
        apiEndpoint: "/api/demo-extended/users",
    },
};
```

### Plugin System

```javascript
// Base module with plugin support
export class DemoUsersTable {
    constructor(plugins = []) {
        this.plugins = plugins;
        this.initializePlugins();
    }

    initializePlugins() {
        this.plugins.forEach((plugin) => plugin.init(this));
    }
}

// Project-specific plugin
export const EmailPlugin = {
    init(component) {
        component.addAction("email", this.sendEmail);
    },
    sendEmail(user) {
        // Project-specific email logic
    },
};
```

---

## 📚 Summary

Choose the right extension pattern based on your needs:

-   **Slots**: For UI customization
-   **Props**: For configuration
-   **Events**: For behavior handling
-   **Inheritance**: For backend logic extension
-   **Composition**: For complex combinations

This approach ensures your modules remain reusable while allowing unlimited project-specific customization! 🎉
