# 🚀 Staff Roster Module Installation Guide

## Prerequisites

-   Laravel 11.x or higher
-   Vue 3.x
-   Vuetify 3.x (for Vuexy theme)
-   Laravel Sanctum for authentication
-   Axios for API calls

## Installation Steps

### 1. Install Module Files

First, you need to get the module files into your project:

```bash
# Option 1: Copy from template project
cp -r /path/to/template/feature-modules/roster ./feature-modules/

# Option 2: Git subtree (if using git)
git subtree add --prefix=feature-modules/roster https://github.com/your-org/template.git staff-roster-module --squash
```

### 2. Backend Installation

#### Add Repository to composer.json

Add this to your `composer.json` file:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./feature-modules/roster/backend"
        }
    ]
}
```

#### Install Backend Package

```bash
composer require staff-roster/staff-roster-module:@dev
```

#### Run Migrations

```bash
php artisan migrate
```

### 3. Frontend Installation

#### Add Dependency to package.json

Add this to your `package.json` dependencies:

```json
{
    "dependencies": {
        "staff-roster-module-frontend": "file:./feature-modules/roster/frontend"
    }
}
```

#### Install Frontend Package

```bash
npm install
```

#### Register Vue Plugin

In your `main.js` file:

```javascript
import StaffRosterModule from "staff-roster-module-frontend";

const app = createApp(App);
app.use(StaffRosterModule);
app.mount("#app");
```

### 4. Configuration

#### Environment Variables

Add these to your `.env` file:

```env
VITE_API_BASE_URL=https://your-domain.test
```

#### Database Tables

The module will create these tables:

-   `staff_roster` - Main roster entries
-   `staff_roster_log` - Shift logging (if using shift-log module)

### 5. Usage

#### Basic Component Usage

```vue
<template>
    <div>
        <StaffRosterTable :site-id="1" />
    </div>
</template>
```

#### With Customization

```vue
<template>
    <StaffRosterTable :site-id="selectedSiteId">
        <template #title>My Custom Roster</template>
        <template #actions="{ loading, reload }">
            <VBtn @click="customAction">Custom Action</VBtn>
        </template>
    </StaffRosterTable>
</template>
```

## Troubleshooting

### Common Issues

1. **Module not found**: Ensure you've installed the module files first
2. **API errors**: Check your VITE_API_BASE_URL setting
3. **Authentication errors**: Verify Laravel Sanctum is properly configured
4. **Component not registered**: Make sure you've registered the Vue plugin

### Debug Commands

```bash
# Check backend installation
composer show staff-roster/staff-roster-module

# Check frontend installation
npm list staff-roster-module-frontend

# Check API routes
php artisan route:list | grep staff-roster
```

## Next Steps

1. Customize the components using slots and props
2. Extend the backend controller for additional functionality
3. Add custom styling to match your theme
4. Implement additional features like reporting

---

**Happy rostering!** 🎯
