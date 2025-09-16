# Shift Log Frontend Module

Vue 3 frontend components for the Shift Log Management system. This module provides comprehensive shift tracking functionality with configurable features.

## Features

### Core Components

-   **ShiftLog.vue** - Admin view for managing all staff shifts
-   **MyShifts.vue** - Individual staff view for personal shifts
-   **StoreUpdateShiftLogDialog.vue** - Check-in/check-out dialog with photo capture
-   **ShiftLogDetailsDialog.vue** - Detailed view of shift logs (admin)
-   **MyShiftDetailsDialog.vue** - Detailed view for staff personal shifts

### Optional Features (Configurable)

-   **Site Integration** - Multi-site shift management
-   **Break Tracking** - Manage shift breaks
-   **Overtime Requests** - Staff overtime tracking and approval
-   **Photo Capture** - Webcam integration for check-in/check-out photos
-   **Comments** - Check-in and check-out notes
-   **IP Tracking** - Location verification

### Built-in Features

-   **Responsive Design** - Mobile, tablet, and desktop optimized
-   **Real-time Updates** - Live shift status updates
-   **Time Variance Detection** - Late/early check-in alerts
-   **Advanced Filtering** - Date, site, staff, and search filters
-   **Pagination** - Efficient large dataset handling

## Installation

### 1. Install NPM Dependencies

```bash
cd feature-modules/shift-log/frontend
npm install
```

### 2. Import Components in Your App

#### Option A: Plugin Installation (Recommended)

```javascript
// main.js or plugin file
import ShiftLogPlugin from "@/../../feature-modules/shift-log/frontend/index.js";

app.use(ShiftLogPlugin, {
    config: {
        enable_sites: true,
        enable_breaks: false,
        enable_overtime: false,
        enable_photos: false,
        enable_comments: true,
        labels: {
            shift: "Shift",
            check_in: "Check In",
            check_out: "Check Out",
            overtime: "Overtime",
        },
    },
});
```

#### Option B: Individual Component Import

```javascript
import {
    ShiftLog,
    MyShifts,
    StoreUpdateShiftLogDialog,
    shiftLogService,
} from "@/../../feature-modules/shift-log/frontend/index.js";
```

### 3. Create Page Components

Create page wrappers for routing:

```vue
<!-- pages/shift-log.vue -->
<script setup>
import { ShiftLog } from "@/../../feature-modules/shift-log/frontend/index.js";
</script>
<template>
    <ShiftLog />
</template>
```

```vue
<!-- pages/my-shifts.vue -->
<script setup>
import { MyShifts } from "@/../../feature-modules/shift-log/frontend/index.js";
</script>
<template>
    <MyShifts />
</template>
```

### 4. Add to Navigation

```javascript
// navigation/vertical/index.js
export default [
    {
        title: "Shift Management",
        icon: { icon: "tabler-clock" },
        children: [
            {
                title: "Shift Log",
                to: { name: "ShiftLog" },
                icon: { icon: "tabler-clock-hour-4" },
            },
            {
                title: "My Shifts",
                to: { name: "MyShifts" },
                icon: { icon: "tabler-user-clock" },
            },
        ],
    },
];
```

### 5. Configure API Endpoints

Ensure these endpoints are added to your `utils/endpoints.js`:

```javascript
export const $endpoints = {
    // ... existing endpoints

    // Shift Log - Admin endpoints
    SHIFT_LOG_INDEX: "api/v1/shift-log",
    SHIFT_LOG_STORE: "api/v1/shift-log",
    SHIFT_LOG_SHOW: "api/v1/shift-log/{id}",
    SHIFT_LOG_UPDATE: "api/v1/shift-log/{id}",
    SHIFT_LOG_DELETE: "api/v1/shift-log/{id}",
    SHIFT_LOG_OPEN_SHIFT: "api/v1/shift-log/open-shift/staff",

    // Staff My Shifts endpoints
    MY_SHIFTS_INDEX: "api/v1/my-shifts",
    MY_SHIFTS_CHECK_IN: "api/v1/my-shifts/check-in",
    MY_SHIFTS_CHECK_OUT: "api/v1/my-shifts/check-out/{id}",
    MY_SHIFTS_OPEN_SHIFT: "api/v1/my-shifts/open-shift",

    // Break management (when enabled)
    SHIFT_LOG_BREAKS_MANAGE: "api/v1/shift-log/{id}/breaks",
    MY_SHIFTS_BREAKS_MANAGE: "api/v1/my-shifts/{id}/breaks",

    // Photo upload
    SHIFT_LOG_UPLOAD_PHOTO: "api/v1/shift-log/upload-photo",

    // Staff and sites
    GET_STAFF_LIST: "staff",
    GET_SITES: "sites",
};
```

## Usage

### Admin Shift Log View

The `ShiftLog` component provides administrators with:

-   **Day-by-day shift overview** with date navigation
-   **Combined roster and log data** showing planned vs actual shifts
-   **Staff and site filtering** for targeted management
-   **Quick check-in/check-out actions** for staff assistance
-   **Shift status indicators** (completed, in-progress, not started)
-   **Time variance alerts** for late/early check-ins

```vue
<template>
    <ShiftLog />
</template>
```

### Staff My Shifts View

The `MyShifts` component allows staff to:

-   **View personal shift history** with past/future toggle
-   **Check in/out of shifts** with time restrictions
-   **View shift details** including breaks and overtime
-   **Handle open shift scenarios** with forced checkout warnings

```vue
<template>
    <MyShifts />
</template>
```

### Check-in/Check-out Dialog

The `StoreUpdateShiftLogDialog` handles:

-   **Flexible check-in/check-out** for both rostered and manual shifts
-   **Photo capture** using device camera (when enabled)
-   **Comments and overtime requests**
-   **Site selection** for manual shifts
-   **Time validation** and conflict resolution

```vue
<template>
    <StoreUpdateShiftLogDialog
        activator-type="check-in"
        :data="shiftData"
        :my-shifts="false"
        :selected-site-id="siteId"
        @refresh="loadShifts"
    />
</template>
```

### Configuration

The module supports dynamic configuration through the backend config or plugin options:

```javascript
const config = {
    enable_sites: true, // Multi-site support
    enable_breaks: false, // Break tracking
    enable_overtime: false, // Overtime requests
    enable_photos: false, // Camera integration
    enable_comments: true, // Check-in/out comments
    enable_ip_tracking: false, // IP address logging

    labels: {
        shift: "Shift",
        check_in: "Check In",
        check_out: "Check Out",
        overtime: "Overtime",
        grouping_label: "Site", // Site/Location/Branch
    },
};
```

## Permissions

The components integrate with your permission system (CASL):

-   `manage_shift_log` - Full admin access to all shifts
-   `view_own_shifts` - Staff can view their own shifts
-   `check_in_out` - Staff can check in/out of shifts

## Dependencies

### Required

-   Vue 3.4+
-   Vuetify 3.5+
-   @vueuse/core 10.9+
-   dayjs 1.11+
-   lodash 4.17+

### Optional (for photo features)

-   Browser camera API support
-   File upload capabilities

## Browser Support

-   **Modern browsers** with ES2020+ support
-   **Camera API** for photo features (HTTPS required)
-   **Responsive design** for mobile devices

## Customization

### Styling

Components use Vuetify's theming system and can be customized through:

-   CSS custom properties
-   Vuetify theme configuration
-   Component prop overrides

### Functionality

-   Override service methods for custom API integration
-   Extend components for additional features
-   Configure optional modules as needed

## Troubleshooting

### Common Issues

1. **Camera not working**: Ensure HTTPS and camera permissions
2. **API endpoints not found**: Verify endpoints.js configuration
3. **Navigation not showing**: Check route definitions and permissions
4. **Photos not uploading**: Verify FormData support and API endpoints

### Performance

-   Uses Vue 3's Composition API for optimal performance
-   Implements efficient data loading with pagination
-   Minimal re-renders through computed properties
-   Lazy loading for large datasets

## Contributing

When extending this module:

1. Follow Vue 3 Composition API patterns
2. Maintain TypeScript compatibility where applicable
3. Update configuration options as needed
4. Add tests for new functionality
5. Update documentation

## License

This module is part of the Template project and follows the same license terms.
