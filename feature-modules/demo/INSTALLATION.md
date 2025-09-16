# Demo Module Installation Guide

This guide explains how to install and use the Demo Module in other Laravel + Vue projects.

## 🎯 Module Overview

The Demo Module provides:

-   **Backend**: Laravel controller, routes, and service provider
-   **Frontend**: Vue components with Vuexy theming
-   **API**: RESTful endpoints for user management
-   **Authentication**: Bearer token integration

## 📦 Installation Methods

### Method 1: Git Subtree from Demo Module Branch (Recommended)

**Step 1: Install the module files**

```bash
# In your target project root - this MUST be done first
git subtree add --prefix=feature-modules/demo https://github.com/nermedin/template.git demo-module --squash
```

**Step 2: Configure Composer repository**
Add to your project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./feature-modules/demo/backend"
        }
    ]
}
```

**Step 3: Install packages**

```bash
# Now Composer can find the package
composer require demo/demo-module:@dev

# Install frontend package
npm install demo-module-frontend
```

**To update the module later:**

```bash
git subtree pull --prefix=feature-modules/demo https://github.com/nermedin/template.git demo-module --squash
composer update demo/demo-module
npm install  # Updates the local file dependency
```

### Method 2: Manual Download + Local Install

**Step 1: Download the module**

```bash
mkdir -p feature-modules
cd feature-modules
git clone -b demo-module https://github.com/nermedin/template.git demo
cd ..
```

**Step 2: Configure Composer and NPM**
Add to your project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./feature-modules/demo/backend"
        }
    ],
    "require": {
        "demo/demo-module": "@dev"
    }
}
```

Add to your project's `package.json`:

```json
{
    "dependencies": {
        "demo-module-frontend": "file:./feature-modules/demo/frontend"
    }
}
```

**Step 3: Install**

```bash
composer install
npm install
```

## 🛠️ Complete Setup Guide

### Step 1: Install Module Files ⚠️ **IMPORTANT: Do this first!**

```bash
# In your target project root
git subtree add --prefix=feature-modules/demo https://github.com/nermedin/template.git demo-module --squash
```

### Step 2: Configure Composer Repository

Edit your project's `composer.json` to add the repository:

```json
{
    "name": "your-project/name",
    "type": "project",
    "repositories": [
        {
            "type": "path",
            "url": "./feature-modules/demo/backend"
        }
    ],
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0"
        // ... your other dependencies
    }
}
```

### Step 3: Install Backend Package

```bash
composer require demo/demo-module:@dev
```

The service provider will be auto-discovered and routes will be automatically loaded.

### Step 4: Configure NPM

Edit your project's `package.json` to add the frontend dependency:

```json
{
    "dependencies": {
        "demo-module-frontend": "file:./feature-modules/demo/frontend"
        // ... your other dependencies
    }
}
```

### Step 5: Install Frontend Package

```bash
npm install
```

### Step 6: Register Vue Components

In your main Vue app file (usually `resources/js/main.js` or `resources/js/app.js`):

```javascript
import DemoModule from "demo-module-frontend";

const app = createApp({});
app.use(DemoModule);
```

Or import components individually:

```javascript
import { DemoUsersTable } from "demo-module-frontend";
```

### Step 7: Use in Your Pages

Create a page that uses the demo module:

```vue
<!-- resources/js/pages/demo.vue -->
<template>
    <VContainer>
        <DemoUsersTable />
    </VContainer>
</template>

<script setup>
import { DemoUsersTable } from "demo-module-frontend";

definePage({
    name: "Demo",
    meta: {
        auth: true,
        layout: "default",
    },
});
</script>
```

### Step 8: Add Navigation (Optional)

Add to your navigation file (e.g., `resources/js/navigation/vertical/index.js`):

```javascript
export default [
    // ... other nav items
    {
        title: "Demo",
        to: { name: "Demo" },
        icon: { icon: "tabler-wrecking-ball" },
    },
];
```

## 🔧 Configuration

### Environment Variables

**API Base URL Configuration:**
The demo module automatically detects your project's API URL using:

1. `VITE_API_BASE_URL` environment variable (if set)
2. Current domain (`window.location.origin`) as fallback

**Recommended**: Set `VITE_API_BASE_URL` in your `.env` file:

```env
VITE_API_BASE_URL=https://your-domain.test
```

**Alternative**: If you don't set `VITE_API_BASE_URL`, the module will use the current domain automatically.

### Authentication

The module expects:

-   **Laravel Sanctum** for API authentication
-   **Bearer tokens** stored in cookies as `accessToken`
-   **User authentication middleware** on API routes

### Database

The module works with the standard Laravel `users` table. Optional columns:

-   `role` (string, nullable) - User role
-   `department` (string, nullable) - User department

Add these with a migration if needed:

```bash
php artisan make:migration add_role_department_to_users_table
```

## 🎨 Customization

### Basic Usage

```vue
<template>
    <DemoUsersTable />
</template>
```

### With Custom Title

```vue
<template>
    <DemoUsersTable>
        <template #title>My Custom User List</template>
    </DemoUsersTable>
</template>
```

### With Custom Actions

```vue
<template>
    <DemoUsersTable @user-action="handleUserAction">
        <template #actions="{ loading, reload }">
            <VBtn @click="customAction">Custom Action</VBtn>
            <VBtn @click="reload">Refresh</VBtn>
        </template>
    </DemoUsersTable>
</template>

<script setup>
const handleUserAction = ({ action, user }) => {
    console.log(`${action} user:`, user);
};

const customAction = () => {
    // Your custom logic
};
</script>
```

### With Custom Headers

```vue
<template>
    <DemoUsersTable :custom-headers="customHeaders" />
</template>

<script setup>
const customHeaders = [
    { title: "ID", key: "id", sortable: true },
    { title: "Name", key: "name", sortable: true },
    { title: "Email", key: "email", sortable: true },
    { title: "Actions", key: "actions", sortable: false },
];
</script>
```

## 🚀 API Endpoints

The module provides these endpoints:

-   `GET /api/demo/users` - Fetch users
-   `POST /api/demo/users` - Create user (if extended)
-   `PUT /api/demo/users/{id}` - Update user (if extended)
-   `DELETE /api/demo/users/{id}` - Delete user (if extended)

## 🔄 Updates

To update the module in your project:

### Git Subtree Method:

```bash
git subtree pull --prefix=feature-modules/demo https://github.com/nermedin/template.git demo-module --squash
composer update demo/demo-module
npm install
```

### Manual Method:

```bash
cd feature-modules
rm -rf demo
git clone -b demo-module https://github.com/nermedin/template.git demo
cd ..
composer update demo/demo-module
npm install
```

## 🐛 Troubleshooting

### Common Issues

1. **"demo/demo-module could not be found"**:

    - ✅ Ensure you ran `git subtree add` **first**
    - ✅ Check that `feature-modules/demo/backend/` exists
    - ✅ Verify `repositories` is added to `composer.json`

2. **"Module not found"**: Ensure the feature-modules directory is in your project root

3. **"API requests failing"**: Module can't connect to your API

    - ✅ Set `VITE_API_BASE_URL=https://your-domain.test` in your `.env` file
    - ✅ Or module will auto-detect using current domain (`window.location.origin`)
    - ✅ Ensure your Laravel API is running and accessible

4. **"Routes not working"**: Check that the service provider is registered

    ```bash
    php artisan package:discover
    ```

5. **"Components not rendering"**: Verify Vue plugin registration

6. **"API errors"**: Check authentication and CORS settings

7. **"Import errors"**: Ensure all dependencies are installed

### Debug Commands

```bash
# Check if files exist
ls -la feature-modules/demo/backend/
ls -la feature-modules/demo/frontend/

# Check if packages are installed
composer show demo/demo-module
npm list demo-module-frontend

# Check routes
php artisan route:list | grep demo

# Check service providers
php artisan package:discover

# Clear caches if needed
php artisan config:clear
php artisan route:clear
composer dump-autoload
```

### Verification Steps

1. **Files exist**: `feature-modules/demo/` directory should be present
2. **Composer config**: Check `repositories` section in `composer.json`
3. **Packages installed**: Both composer and npm packages should be installed
4. **Routes registered**: Demo routes should appear in route list
5. **Components available**: Should import without errors

## 📞 Support

For issues or questions:

1. Check the [Module Creation Guide](../../docs/MODULE_CREATION_GUIDE.md)
2. Review the [Extension Guide](../../docs/MODULE_EXTENSION_GUIDE.md)
3. Examine the working example in the template project
4. Verify all steps were followed in order

## 🎉 Success!

You should now have a working demo module in your project! Visit `/demo` to see it in action.

**✅ Quick verification checklist:**

-   [ ] Module files exist in `feature-modules/demo/`
-   [ ] Composer repository added to `composer.json`
-   [ ] Backend package installed with Composer
-   [ ] Frontend package installed with NPM
-   [ ] Vue components registered
-   [ ] Demo page created
-   [ ] Navigation added (optional)

---

**Happy Coding!** 🚀
