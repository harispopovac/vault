# 🕒 Shift Log Module

A comprehensive full-stack shift log management module for Laravel + Vue projects that combines planned shifts (rosters) with actual shift tracking.

## 📋 What's Included

### Backend Package (`backend/`)

-   **Controller**: `StaffRosterLogController` with combined query functionality
-   **Repository**: `StaffRosterLogRepository` with complex qobox-style query
-   **Models**: `StaffRosterLog` and `StaffRosterShiftBreak` with configurable relationships
-   **Form Requests**: `CheckInRequest` and `CheckOutRequest` for validation
-   **Service Provider**: Auto-registration and route loading
-   **Routes**: RESTful API endpoints for both admin and staff views
-   **Composer Package**: Ready to install with `composer require`

### Frontend Package (`frontend/`)

-   **Vue Components**: `ShiftLogTable` and `MyShiftsView` components
-   **API Service**: Axios-based service with authentication
-   **Vue Plugin**: Easy registration and component exports
-   **NPM Package**: Ready to install with `npm install`

## 🚀 Features

### ✅ **Core Functionality**

-   **Combined View**: Shows both planned shifts (rosters) and actual logged shifts
-   **Manual Check-ins**: Supports shift logs without associated roster entries
-   **Check-in/Check-out**: Full shift lifecycle management
-   **Open Shift Detection**: Prevents multiple open shifts per staff member
-   **Admin View**: All staff members' shifts with filtering
-   **My Shifts View**: Individual staff member's shift history

### ✅ **Configurable Features**

-   **Sites Integration**: Optional site-based filtering (like roster module)
-   **Break Tracking**: Optional break management during shifts
-   **Overtime Claims**: Optional overtime tracking and approval
-   **Photo Uploads**: Optional check-in/check-out photos
-   **IP Tracking**: Optional IP address logging
-   **Comments**: Optional check-in/check-out comments

### ✅ **Advanced Query**

-   **Complex SQL Query**: Replicates qobox's sophisticated combined query
-   **Timezone Support**: Automatic timezone handling per site
-   **Search & Filter**: By site, staff, date, and status
-   **Past/Future Views**: Toggle between historical and upcoming shifts

## 🛠️ Installation

### Backend Installation

```bash
# 1. Add to composer.json repositories
{
    "repositories": [
        {
            "type": "path",
            "url": "./feature-modules/shift-log/backend"
        }
    ]
}

# 2. Install backend package
composer require shift-log/shift-log-module:@dev

# 3. Publish configuration
php artisan vendor:publish --tag=shift-log-config

# 4. Run migrations (tables already in schema.sql)
php artisan migrate
```

### Frontend Installation

```bash
# 1. Add to package.json
{
    "dependencies": {
        "shift-log-module-frontend": "file:./feature-modules/shift-log/frontend"
    }
}

# 2. Install frontend package
npm install

# 3. Register Vue plugin
import ShiftLogModule from 'shift-log-module-frontend'
app.use(ShiftLogModule)
```

## ⚙️ Configuration

### Environment Variables

Add these to your `.env` file:

```env
# Site Integration (matches roster module)
SHIFT_LOG_ENABLE_SITES=true
SHIFT_LOG_SITE_MODEL="Sites\\SitesModule\\Models\\Site"
SHIFT_LOG_SITE_TABLE="sites"

# Staff Integration
SHIFT_LOG_STAFF_MODEL="App\\Models\\User"
SHIFT_LOG_STAFF_TABLE="users"

# Optional Features
SHIFT_LOG_ENABLE_BREAKS=false
SHIFT_LOG_ENABLE_OVERTIME=false
SHIFT_LOG_ENABLE_PHOTOS=false
SHIFT_LOG_ENABLE_IP_TRACKING=false
SHIFT_LOG_ENABLE_COMMENTS=true

# Labels
SHIFT_LOG_SHIFT_LABEL="Shift"
SHIFT_LOG_CHECKIN_LABEL="Check In"
SHIFT_LOG_CHECKOUT_LABEL="Check Out"
```

## 📡 API Endpoints

### Administrative Endpoints

```
GET    /api/v1/shift-log              # Combined shifts + logs query
POST   /api/v1/shift-log              # Check in to shift
GET    /api/v1/shift-log/{id}         # Get specific shift log
PUT    /api/v1/shift-log/{id}         # Check out from shift
DELETE /api/v1/shift-log/{id}         # Delete shift log
GET    /api/v1/shift-log/open-shift/staff  # Get open shift for staff
```

### Staff "My Shifts" Endpoints

```
GET    /api/v1/my-shifts              # My shifts only
POST   /api/v1/my-shifts/check-in     # Check in to my shift
POST   /api/v1/my-shifts/check-out/{id}  # Check out from my shift
GET    /api/v1/my-shifts/open-shift   # Get my open shift
```

### Optional Break Endpoints (when enabled)

```
POST   /api/v1/shift-log/{id}/breaks     # Manage breaks (admin)
POST   /api/v1/my-shifts/{id}/breaks     # Manage my breaks (staff)
```

## 🎯 Usage Examples

### Basic Admin View

```vue
<template>
    <ShiftLogTable :site-id="selectedSiteId" :show-all-staff="true" />
</template>
```

### Staff "My Shifts" View

```vue
<template>
    <MyShiftsView :staff-id="currentUser.id" :show-past-shifts="false" />
</template>
```

### With Optional Features

```vue
<template>
    <ShiftLogTable
        :site-id="selectedSiteId"
        :enable-breaks="true"
        :enable-overtime="true"
        :enable-photos="true"
    >
        <template #actions="{ shift }">
            <VBtn @click="approveOvertime(shift)"> Approve OT </VBtn>
        </template>
    </ShiftLogTable>
</template>
```

## 🔄 How the Combined Query Works

The module replicates qobox's sophisticated query logic:

1. **Planned Shifts CTE**: Gets all planned shifts from `staff_roster` table
2. **Actual Logs CTE**: Gets all actual shift logs from `staff_roster_log` table
3. **LEFT JOIN**: Combines planned shifts with their corresponding logs
4. **UNION ALL**: Adds manual shift logs (not linked to planned shifts)
5. **Timezone Handling**: Converts times based on site timezone
6. **Filtering**: Applies site, staff, date, and search filters

This creates a unified view showing:

-   ✅ **Planned Only**: Shifts scheduled but not started
-   ✅ **Logged Shifts**: Planned shifts with actual check-in/out
-   ✅ **Manual Logs**: Check-ins without corresponding roster entry
-   ✅ **Open Shifts**: Currently active (checked in, not out)
-   ✅ **Completed**: Fully logged shifts

## 🎨 Customization

### Optional Features

Features can be enabled/disabled per project:

```php
// For TDT (no breaks)
'enable_breaks' => false,

// For projects with overtime tracking
'enable_overtime' => true,

// For projects without sites
'enable_sites' => false,
```

### Different Models

Configure different staff and site models:

```php
// Use different staff table
'staff_model' => 'App\\Models\\Staff',
'staff_table' => 'staff',

// Use different site model
'site_model' => 'App\\Models\\Location',
'site_table' => 'locations',
```

## 🔐 Security & Authorization

Authorization checks are built into the controller (commented out for initial setup):

```php
// Enable in production
$this->authorize('see_shift_log', [StaffRosterLog::class, ['site_id' => $request->query('site_id')]]);
$this->authorize('manage_shift_log', $staffRosterLog);
```

## 📊 Database Schema

The module uses these tables:

-   **`staff_roster_log`**: Main shift logging table
-   **`staff_roster_shift_breaks`**: Optional break tracking (when enabled)
-   **`staff_approved_breaks`**: Optional approved breaks (when enabled)

All optional fields are nullable and handled by configuration.

## 🔗 Integration

Works seamlessly with:

-   ✅ **Roster Module**: Links to planned shifts
-   ✅ **Sites Module**: Optional site-based filtering
-   ✅ **Media Library**: Optional photo uploads
-   ✅ **Laravel Sanctum**: Authentication
-   ✅ **Vue 3 + Vuetify**: Frontend components

---

**Ready to track shifts like qobox!** ⏰

## 📄 License

This shift log module is provided for educational and development purposes.
