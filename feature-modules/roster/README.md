# 🎯 Staff Roster Module

A complete full-stack staff roster management module for Laravel + Vue projects with Vuexy theming.

## 📋 What's Included

### Backend Package (`backend/`)

-   **Controller**: `StaffRosterController` with full CRUD operations
-   **Repository**: `StaffRosterRepository` with advanced querying
-   **Model**: `StaffRoster` with relationships
-   **Service Provider**: Auto-registration and route loading
-   **Routes**: RESTful API endpoints for roster operations
-   **Composer Package**: Ready to install with `composer require`

### Frontend Package (`frontend/`)

-   **Vue Component**: `StaffRosterTable` with Vuexy theming
-   **API Service**: Axios-based service with authentication
-   **Vue Plugin**: Easy registration and component exports
-   **NPM Package**: Ready to install with `npm install`

## 🚀 Features

-   ✅ **Staff Roster Management**: Create, view, edit, and delete roster entries
-   ✅ **Weekly View**: Calendar-style weekly roster display
-   ✅ **Copy/Paste**: Copy rosters between weeks and days
-   ✅ **Timezone Support**: Automatic timezone handling per site
-   ✅ **Authentication**: Bearer token integration with cookies
-   ✅ **Theming**: Full Vuexy theme integration
-   ✅ **Responsive**: Mobile-friendly interface
-   ✅ **Extensible**: Slots, props, and events for customization
-   ✅ **Loading States**: Proper loading and error handling
-   ✅ **Search & Filter**: Advanced filtering capabilities
-   ✅ **Absence Management**: Track staff absences and notes

## 🛠️ Quick Start

### Installation

```bash
# 1. Install backend package
composer require staff-roster/staff-roster-module:@dev

# 2. Install frontend package
npm install staff-roster-module-frontend

# 3. Register Vue plugin in your main.js
import StaffRosterModule from 'staff-roster-module-frontend'
app.use(StaffRosterModule)
```

### Basic Usage

```vue
<template>
    <VContainer>
        <StaffRosterTable :site-id="selectedSiteId" />
    </VContainer>
</template>
```

## 📁 File Structure

```
staff-roster/
├── backend/
│   ├── src/
│   │   ├── StaffRosterServiceProvider.php
│   │   ├── Http/Controllers/StaffRosterController.php
│   │   ├── Models/StaffRoster.php
│   │   └── Repositories/StaffRosterRepository.php
│   ├── routes/api.php
│   └── composer.json
├── frontend/
│   ├── src/
│   │   ├── components/StaffRosterTable.vue
│   │   └── services/staffRosterService.js
│   ├── index.js
│   └── package.json
└── README.md
```

## 🎨 Customization

### Slots Available

-   `title`: Custom title for the roster table
-   `actions`: Custom action buttons
-   `filters`: Additional filter components
-   `cell-content`: Custom cell content

### Props

-   `site-id`: Site ID for filtering rosters
-   `staff-ids`: Array of staff IDs to filter
-   `readonly`: Make the table read-only
-   `show-actions`: Show/hide action buttons

### Events

-   `roster-created`: Emitted when a new roster is created
-   `roster-updated`: Emitted when a roster is updated
-   `roster-deleted`: Emitted when a roster is deleted

## 🔧 API Endpoints

-   `GET /api/staff-roster` - List rosters with filters
-   `POST /api/staff-roster` - Create new roster
-   `GET /api/staff-roster/{id}` - Get specific roster
-   `PUT /api/staff-roster/{id}` - Update roster
-   `DELETE /api/staff-roster/{id}` - Delete roster
-   `POST /api/staff-roster/copy` - Copy rosters between weeks
-   `POST /api/staff-roster/clear` - Clear rosters for a period

## 📄 License

This roster module is provided for educational and development purposes.

---

**Ready to manage staff rosters efficiently!** 🎉
