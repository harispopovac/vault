# 🎯 Demo Module

A complete full-stack demo module for Laravel + Vue projects with Vuexy theming.

## 📋 What's Included

### Backend Package (`backend/`)

-   **Controller**: `DemoController` with user management endpoints
-   **Service Provider**: Auto-registration and route loading
-   **Routes**: RESTful API endpoints for user operations
-   **Composer Package**: Ready to install with `composer require`

### Frontend Package (`frontend/`)

-   **Vue Component**: `DemoUsersTable` with Vuexy theming
-   **API Service**: Axios-based service with authentication
-   **Vue Plugin**: Easy registration and component exports
-   **NPM Package**: Ready to install with `npm install`

## 🚀 Features

-   ✅ **User Management**: Display, view, and manage users
-   ✅ **Authentication**: Bearer token integration with cookies
-   ✅ **Theming**: Full Vuexy theme integration
-   ✅ **Responsive**: Mobile-friendly data tables
-   ✅ **Extensible**: Slots, props, and events for customization
-   ✅ **Loading States**: Proper loading and error handling
-   ✅ **API Integration**: RESTful endpoints with Laravel Sanctum

## 🛠️ Quick Start

### In Another Project

Follow the [Installation Guide](./INSTALLATION.md) for complete setup instructions.

**Quick Install:**

```bash
# 1. Install the module files (MUST be done first!)
git subtree add --prefix=feature-modules/demo https://github.com/nermedin/template.git demo-module --squash

# 2. Configure composer repository in your composer.json
{
  "repositories": [
    {
      "type": "path",
      "url": "./feature-modules/demo/backend"
    }
  ]
}

# 3. Install backend package
composer require demo/demo-module:@dev

# 4. Configure npm dependency in your package.json
# Add this to your package.json "dependencies":
{
  "dependencies": {
    "demo-module-frontend": "file:./feature-modules/demo/frontend"
  }
}

# 5. Install frontend package
npm install

# 6. Register Vue plugin in your main.js:
# import DemoModule from 'demo-module-frontend'
# app.use(DemoModule)
```

**⚠️ Critical**: You MUST install the module files first with `git subtree add` before configuring package.json, otherwise `npm install` will fail with 404 errors. See the [Installation Guide](./INSTALLATION.md) for complete details.

### Basic Usage

After installation, use the component in your Vue pages:

```vue
<template>
    <VContainer>
        <DemoUsersTable />
    </VContainer>
</template>

<script setup>
// Import individual components (if not using plugin)
// import { DemoUsersTable } from 'demo-module-frontend'

// Or use globally registered component (if using plugin)
// Component is automatically available as <DemoUsersTable>
</script>
```

## 🎨 Customization Examples

### Custom Title & Actions

```vue
<template>
    <DemoUsersTable>
        <template #title>My User Management</template>
        <template #actions="{ loading, reload }">
            <VBtn @click="reload">Refresh</VBtn>
        </template>
    </DemoUsersTable>
</template>
```

### Event Handling

```vue
<template>
    <DemoUsersTable @user-action="handleUserAction" />
</template>

<script setup>
const handleUserAction = ({ action, user }) => {
    if (action === "view") {
        router.push(`/users/${user.id}`);
    }
};
</script>
```

## 🔧 Requirements

-   **Laravel**: 11.x or higher
-   **Vue**: 3.x
-   **Vuetify**: 3.x (for Vuexy theme)
-   **Laravel Sanctum**: For authentication
-   **Axios**: For API calls

## ⚙️ Configuration

### API Base URL

The module automatically detects your project's API URL using:

1. `VITE_API_BASE_URL` environment variable (if set)
2. Current domain (`window.location.origin`) as fallback

**Recommended**: Set `VITE_API_BASE_URL` in your `.env` file:

```env
VITE_API_BASE_URL=https://your-domain.test
```

## 📁 File Structure

```
demo/
├── backend/
│   ├── src/
│   │   ├── DemoServiceProvider.php
│   │   └── Http/Controllers/DemoController.php
│   ├── routes/api.php
│   └── composer.json
├── frontend/
│   ├── src/
│   │   ├── components/DemoUsersTable.vue
│   │   └── services/demoService.js
│   ├── index.js
│   └── package.json
├── INSTALLATION.md
└── README.md
```

## 🚀 API Endpoints

-   `GET /api/demo/users` - Fetch users list
-   `GET /api/demo/users/{id}` - Get specific user (if implemented)
-   `POST /api/demo/users` - Create user (if extended)
-   `PUT /api/demo/users/{id}` - Update user (if extended)
-   `DELETE /api/demo/users/{id}` - Delete user (if extended)

## 🎯 Extension Points

This module is designed to be extended. See the template project's extended example:

-   **ExtendedDemoTable.vue** - Shows slot customization
-   **ExtendedDemoController.php** - Shows controller inheritance
-   **extendedDemoService.js** - Shows service extension

## 🐛 Troubleshooting

**Common Issues:**

1. **NPM 404 Error**: `demo-module-frontend` is not on npm registry

    - ✅ **Solution**: Install module files first with `git subtree add`
    - ✅ **Then**: Configure `package.json` with `"demo-module-frontend": "file:./feature-modules/demo/frontend"`
    - ✅ **Finally**: Run `npm install` (not `npm install demo-module-frontend`)

2. **API Connection Issues**: Module can't reach your API

    - ✅ **Solution**: Set `VITE_API_BASE_URL=https://your-domain.test` in your `.env` file
    - ✅ **Or**: Module will auto-detect using current domain
    - ✅ **Check**: Ensure your Laravel API is running on the correct URL

3. **Authentication errors**: Ensure `accessToken` cookie is set
4. **CORS issues**: Check Laravel CORS configuration
5. **Component not found**: Verify Vue plugin registration
6. **API not found**: Check service provider registration

**Debug Commands:**

```bash
php artisan route:list | grep demo
composer show demo/demo-module
npm list demo-module-frontend
```

## 📖 Documentation

-   [Installation Guide](./INSTALLATION.md)
-   [Module Creation Guide](../../docs/MODULE_CREATION_GUIDE.md)
-   [Extension Guide](../../docs/MODULE_EXTENSION_GUIDE.md)

## 📄 License

This demo module is provided for educational and development purposes.

---

**Ready to build modular applications!** 🎉
