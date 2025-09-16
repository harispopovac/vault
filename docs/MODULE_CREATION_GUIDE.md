# Module Creation Guide

This guide explains how to create reusable full-stack feature modules from scratch using Laravel + Vue + Vuexy theme.

## 🎯 Module Overview

A feature module is a **self-contained**, **reusable** package that includes:

-   **Backend**: Laravel controllers, routes, models, migrations
-   **Frontend**: Vue components, services, stores
-   **Dependencies**: Composer and NPM packages
-   **Configuration**: Service providers and module exports

---

## 📁 Module Structure

```
feature-modules/
└── your-module-name/
    ├── backend/
    │   ├── src/
    │   │   ├── YourModuleServiceProvider.php
    │   │   └── Http/Controllers/
    │   │       └── YourController.php
    │   ├── routes/
    │   │   └── api.php
    │   ├── database/
    │   │   └── migrations/
    │   ├── resources/
    │   │   └── views/
    │   └── composer.json
    └── frontend/
        ├── src/
        │   ├── components/
        │   │   └── YourComponent.vue
        │   └── services/
        │       └── yourService.js
        ├── index.js
        └── package.json
```

---

## 🚀 Step-by-Step Module Creation

### Step 1: Create Module Directory

```bash
mkdir -p feature-modules/your-module-name/{backend,frontend}
cd feature-modules/your-module-name
```

### Step 2: Create Backend Structure

#### 2.1 Create Backend Directories

```bash
mkdir -p backend/{src/Http/Controllers,routes,database/migrations,resources/views}
```

#### 2.2 Create `backend/composer.json`

```json
{
    "name": "yourvendor/your-module-name",
    "description": "Description of your module",
    "type": "library",
    "autoload": {
        "psr-4": {
            "YourVendor\\YourModule\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": ["YourVendor\\YourModule\\YourModuleServiceProvider"]
        }
    },
    "require": {
        "illuminate/support": "^11.0"
    }
}
```

#### 2.3 Create Service Provider

```php
<?php
// backend/src/YourModuleServiceProvider.php

namespace YourVendor\YourModule;

use Illuminate\Support\ServiceProvider;

class YourModuleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'your-module');

        // Publish assets if needed
        $this->publishes([
            __DIR__.'/../config/your-module.php' => config_path('your-module.php'),
        ], 'config');
    }

    public function register()
    {
        // Register services
        $this->mergeConfigFrom(
            __DIR__.'/../config/your-module.php', 'your-module'
        );
    }
}
```

#### 2.4 Create Controller

```php
<?php
// backend/src/Http/Controllers/YourController.php

namespace YourVendor\YourModule\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class YourController extends Controller
{
    public function index()
    {
        try {
            // Your logic here
            $data = []; // Fetch your data

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch data'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        // Validation
        $request->validate([
            'name' => 'required|string|max:255',
            // Add your validation rules
        ]);

        try {
            // Create logic here

            return response()->json([
                'success' => true,
                'message' => 'Created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create'
            ], 500);
        }
    }
}
```

#### 2.5 Create Routes

```php
<?php
// backend/routes/api.php

use Illuminate\Support\Facades\Route;
use YourVendor\YourModule\Http\Controllers\YourController;

Route::prefix('api/your-module')->group(function () {
    Route::get('/items', [YourController::class, 'index']);
    Route::post('/items', [YourController::class, 'store']);
    Route::get('/items/{id}', [YourController::class, 'show']);
    Route::put('/items/{id}', [YourController::class, 'update']);
    Route::delete('/items/{id}', [YourController::class, 'destroy']);
});
```

### Step 3: Create Frontend Structure

#### 3.1 Create Frontend Directories

```bash
mkdir -p frontend/src/{components,services,stores}
```

#### 3.2 Create `frontend/package.json`

```json
{
    "name": "your-module-name-frontend",
    "version": "1.0.0",
    "description": "Frontend components for your module",
    "main": "index.js",
    "dependencies": {
        "vue": "^3.0.0",
        "axios": "^1.0.0"
    }
}
```

#### 3.3 Create Vue Component

```vue
<!-- frontend/src/components/YourComponent.vue -->
<template>
    <VCard>
        <VCardText class="d-flex align-center justify-space-between">
            <h4 class="text-h4">
                <slot name="title">{{ title }}</slot>
            </h4>

            <div class="d-flex align-center gap-4">
                <slot
                    name="actions"
                    :loading="loading"
                    :items="items"
                    :reload="loadItems"
                >
                    <VBtn
                        prepend-icon="tabler-refresh"
                        @click="loadItems"
                        :loading="loading"
                    >
                        Refresh
                    </VBtn>
                </slot>
            </div>
        </VCardText>

        <VDivider />

        <VDataTable
            :headers="computedHeaders"
            :items="items"
            :loading="loading"
            no-data-text="No items found"
            class="text-no-wrap"
        >
            <!-- Custom slots for each column -->
            <template
                v-for="header in computedHeaders"
                :key="header.key"
                #[`item.${header.key}`]="{ item }"
            >
                <slot
                    :name="`column.${header.key}`"
                    :item="item"
                    :value="item[header.key]"
                >
                    {{ item[header.key] }}
                </slot>
            </template>

            <!-- Actions column -->
            <template #item.actions="{ item }">
                <slot
                    name="row-actions"
                    :item="item"
                    :viewItem="viewItem"
                    :editItem="editItem"
                >
                    <VBtn
                        icon
                        size="small"
                        variant="text"
                        @click="viewItem(item)"
                    >
                        <VIcon icon="tabler-eye" size="16" />
                    </VBtn>
                    <VBtn
                        icon
                        size="small"
                        variant="text"
                        @click="editItem(item)"
                    >
                        <VIcon icon="tabler-edit" size="16" />
                    </VBtn>
                </slot>
            </template>

            <!-- Loading state -->
            <template #loading>
                <div class="text-center pa-6">
                    <VProgressCircular indeterminate color="primary" />
                    <p class="mt-2 mb-0">Loading...</p>
                </div>
            </template>

            <!-- No data state -->
            <template #no-data>
                <div class="text-center pa-8">
                    <VIcon
                        icon="tabler-database"
                        size="48"
                        color="disabled"
                        class="mb-2"
                    />
                    <h6 class="text-h6 mb-2">No Data Found</h6>
                    <p class="text-body-2 mb-0">
                        There are no items to display
                    </p>
                </div>
            </template>
        </VDataTable>

        <!-- Error state -->
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
import { ref, onMounted, computed } from "vue";
import { fetchItems } from "../services/yourService.js";

// Props for customization
const props = defineProps({
    title: {
        type: String,
        default: "Your Module Items",
    },
    customHeaders: {
        type: Array,
        default: () => [],
    },
    hideDefaultActions: {
        type: Boolean,
        default: false,
    },
    apiEndpoint: {
        type: String,
        default: null,
    },
});

// Emits for parent communication
const emit = defineEmits(["item-selected", "item-action"]);

// Default headers
const defaultHeaders = [
    { title: "ID", key: "id", sortable: true, width: "80px" },
    { title: "Name", key: "name", sortable: true },
    { title: "Created At", key: "created_at", sortable: true, width: "150px" },
    { title: "Actions", key: "actions", sortable: false, width: "120px" },
];

// Computed headers
const computedHeaders = computed(() => {
    if (props.customHeaders.length > 0) {
        return props.customHeaders;
    }
    if (props.hideDefaultActions) {
        return defaultHeaders.filter((h) => h.key !== "actions");
    }
    return defaultHeaders;
});

// Reactive data
const items = ref([]);
const loading = ref(false);
const error = ref(null);

// Load items function
const loadItems = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await fetchItems(props.apiEndpoint);
        items.value = response.data;
    } catch (err) {
        error.value = "Failed to load items. Please try again.";
        console.error("Error loading items:", err);
    } finally {
        loading.value = false;
    }
};

// Action functions
const viewItem = (item) => {
    emit("item-action", { action: "view", item });
};

const editItem = (item) => {
    emit("item-action", { action: "edit", item });
};

// Initialize
onMounted(() => {
    loadItems();
});
</script>

<style scoped>
.text-no-wrap {
    white-space: nowrap;
}
</style>
```

#### 3.4 Create Service

```javascript
// frontend/src/services/yourService.js
import axios from "axios";

// Helper function to get cookie value
const getCookie = (name) => {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(";").shift();
    return null;
};

// Create axios instance
const api = axios.create({
    baseURL: "https://template.test", // Update with your domain
    headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
    },
});

// Add request interceptor for authentication
api.interceptors.request.use(
    (config) => {
        const accessToken = getCookie("accessToken");
        if (accessToken) {
            config.headers.Authorization = `Bearer ${accessToken}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    },
);

// API functions
export const fetchItems = async (customEndpoint = null) => {
    const endpoint = customEndpoint || "/api/your-module/items";
    const response = await api.get(endpoint);
    return response.data;
};

export const createItem = async (itemData) => {
    const response = await api.post("/api/your-module/items", itemData);
    return response.data;
};

export const updateItem = async (itemId, itemData) => {
    const response = await api.put(
        `/api/your-module/items/${itemId}`,
        itemData,
    );
    return response.data;
};

export const deleteItem = async (itemId) => {
    const response = await api.delete(`/api/your-module/items/${itemId}`);
    return response.data;
};

export const getItemById = async (itemId) => {
    const response = await api.get(`/api/your-module/items/${itemId}`);
    return response.data;
};
```

#### 3.5 Create Module Export

```javascript
// frontend/index.js
import YourComponent from "./src/components/YourComponent.vue";

export default {
    install(app) {
        app.component("YourComponent", YourComponent);
    },
};

// Export components individually
export { YourComponent };

// Export services
export * from "./src/services/yourService.js";
```

### Step 4: Install Module in Main Project

#### 4.1 Add Backend Package

```json
// Add to main project's composer.json
{
    "repositories": [
        {
            "type": "path",
            "url": "./feature-modules/your-module-name/backend"
        }
    ],
    "require": {
        "yourvendor/your-module-name": "@dev"
    }
}
```

```bash
composer update yourvendor/your-module-name
```

#### 4.2 Add Frontend Package

```json
// Add to main project's package.json
{
    "dependencies": {
        "your-module-name-frontend": "file:./feature-modules/your-module-name/frontend"
    }
}
```

```bash
npm install
```

#### 4.3 Use in Your Project

```vue
<template>
    <YourComponent title="My Custom Title" @item-action="handleItemAction">
        <template #actions="{ loading, items, reload }">
            <VBtn @click="customAction">Custom Action</VBtn>
        </template>
    </YourComponent>
</template>

<script setup>
import { YourComponent } from "your-module-name-frontend";

const handleItemAction = ({ action, item }) => {
    console.log(`${action} item:`, item);
};

const customAction = () => {
    console.log("Custom action triggered");
};
</script>
```

---

## 🎨 Design Patterns

### 1. **Extensible Components**

-   Use slots for UI customization
-   Use props for configuration
-   Emit events for parent communication

### 2. **Flexible Backend**

-   Create base controllers that can be extended
-   Use service providers for automatic registration
-   Support configuration overrides

### 3. **Modular Services**

-   Create reusable API functions
-   Support custom endpoints
-   Include authentication handling

---

## ✅ Best Practices

### 1. **Naming Conventions**

-   **Backend**: PascalCase for classes, camelCase for methods
-   **Frontend**: PascalCase for components, camelCase for functions
-   **Files**: kebab-case for file names

### 2. **Dependencies**

-   Keep dependencies minimal
-   Use peer dependencies when possible
-   Include only what's necessary

### 3. **Documentation**

-   Document all props and events
-   Include usage examples
-   Explain extension points

### 4. **Testing**

-   Write tests for both backend and frontend
-   Test extension scenarios
-   Include integration tests

### 5. **Version Control**

-   Use semantic versioning
-   Tag releases properly
-   Maintain changelogs

---

## 🚀 Advanced Features

### Configuration-Based Modules

```javascript
// config/modules.js
export const moduleConfig = {
    yourModule: {
        apiEndpoint: "/api/custom-endpoint",
        features: {
            export: true,
            import: false,
            delete: true,
        },
        theme: {
            primaryColor: "blue",
            variant: "outlined",
        },
    },
};
```

### Plugin System

```javascript
// Module with plugin support
export class YourModule {
    constructor(plugins = []) {
        this.plugins = plugins;
        this.initializePlugins();
    }

    initializePlugins() {
        this.plugins.forEach((plugin) => plugin.init(this));
    }

    addAction(name, handler) {
        this.actions[name] = handler;
    }
}
```

### Middleware Support

```php
// Backend with middleware
Route::prefix('api/your-module')
  ->middleware(['auth:sanctum', 'your-module.permissions'])
  ->group(function () {
    // Your routes
  });
```

---

## 📦 Publishing Modules

### NPM Package

```bash
# In frontend directory
npm publish
```

### Composer Package

```bash
# In backend directory
composer publish
```

### Git Subtree (Recommended)

```bash
# Split module into separate repository
git subtree push --prefix=feature-modules/your-module-name origin your-module-name
```

---

## 🔧 Troubleshooting

### Common Issues

1. **Module not discovered**: Check service provider registration
2. **Frontend import errors**: Verify package.json and index.js exports
3. **Route conflicts**: Use unique prefixes for API routes
4. **Authentication issues**: Ensure token passing in API calls

### Debug Commands

```bash
# Laravel
php artisan route:list | grep your-module
php artisan package:discover

# NPM
npm ls your-module-name-frontend
```

---

## 📚 Examples

Check out these working examples:

-   `feature-modules/demo/` - Simple user listing module
-   `resources/js/components/ExtendedDemoTable.vue` - Extended component example
-   `app/Http/Controllers/ExtendedDemoController.php` - Extended controller example

---

## 🎉 Summary

You now know how to:
✅ Create module directory structure
✅ Build Laravel backend packages
✅ Create Vue frontend components
✅ Install modules in projects
✅ Extend modules for specific needs
✅ Follow best practices

Start creating your reusable feature modules and build a powerful, modular application architecture! 🚀
