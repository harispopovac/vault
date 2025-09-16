# Colors Module

A complete color management system with both basic predefined colors and custom color creation capabilities.

## Features

- 🎨 **Basic Colors**: Predefined color palette with light/dark theme support
- 🖌️ **Custom Colors**: Create and manage custom colors with UUID-based storage
- 🔄 **Reusable Components**: Vue 3 + Vuetify components ready for any project
- 🎯 **Smart API**: Laravel backend with proper CRUD operations and UUID support
- 📱 **Responsive**: Works perfectly on all device sizes
- ⚡ **Cached**: Built-in color caching system for performance

## Components

### ColorPickerForm
The main reusable component for color selection and creation.

```vue
<template>
  <!-- Basic usage - shows predefined colors -->
  <ColorPickerForm v-model="selectedColor" @color-selected="onColorSelected" />
  
  <!-- Advanced usage - includes custom color creation -->
  <ColorPickerForm 
    v-model="selectedColor" 
    :advanced="true" 
    @color-selected="onColorSelected" 
  />
</template>
```

### ColorsTable
Demo component showing both basic and custom colors in a table format.

```vue
<template>
  <ColorsTable />
</template>
```

## Utilities

### useColorPalette
Main utility for accessing colors by name (basic) or UUID (custom).

```javascript
import { getColorFromPalette, preloadColors, useColorUpdates } from './utils/useColorPallete.js'

// Get any color (basic or custom)
const color = getColorFromPalette('Purple') // Basic color
const customColor = getColorFromPalette('uuid-string') // Custom color

// Preload multiple colors
preloadColors(['Purple', 'Steel', 'some-uuid'])

// Listen for color updates
const cleanup = useColorUpdates((uuid, color) => {
  console.log('Color updated:', uuid, color)
})
```

## Installation

### Using Git Subtree (Recommended)

1. Add the colors module to your project:
```bash
git subtree add --prefix=feature-modules/colors \
  git@github.com:nermedin/template.git colors-module --squash
```

2. Install backend package:
```json
// Add to composer.json
{
    "repositories": [
        {
            "type": "path",
            "url": "./feature-modules/colors/backend"
        }
    ],
    "require": {
        "colors/colors-module": "@dev"
    }
}
```

```bash
composer update colors/colors-module
```

3. Install frontend package:
```json
// Add to package.json
{
    "dependencies": {
        "colors-frontend": "file:./feature-modules/colors/frontend"
    }
}
```

```bash
npm install
```

### Update Module

To get latest updates from the source:
```bash
git subtree pull --prefix=feature-modules/colors \
  git@github.com:nermedin/template.git colors-module --squash
```

## API Endpoints

- `GET /api/v1/colors` - List all custom colors
- `POST /api/v1/colors` - Create new custom color
- `GET /api/v1/colors/uuid/{uuid}` - Get color by UUID
- `PUT /api/v1/colors/uuid/{uuid}` - Update color by UUID
- `DELETE /api/v1/colors/{id}` - Delete color by ID

## Database Schema

```sql
CREATE TABLE colors (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    name VARCHAR(15),
    dark_text CHAR(10),
    dark_bg CHAR(10),
    dark_border CHAR(10),
    light_text CHAR(10),
    light_bg CHAR(10),
    light_border CHAR(10)
);
```

## Color Data Structure

### Basic Colors
```javascript
{
  name: "Purple",
  light: {
    background: "#9933FF1F",
    text: "#8844FFCC", 
    border: "#9933FF1F"
  },
  dark: {
    background: "#9933FF38",
    text: "#8844FFFF",
    border: "#9933FF38"
  }
}
```

### Custom Colors
```javascript
{
  id: 1,
  uuid: "41db6990-639f-4188-acd3-79008466d4f5",
  name: "Custom-815929",
  light: {
    background: "A892BF1F",
    text: "8844FFCC",
    border: "9933FF1F"
  },
  dark: {
    background: "9933FF38", 
    text: "8844FFFF",
    border: "9933FF38"
  }
}
```

## Usage Examples

### Theme Integration
```javascript
// Get user's selected theme color
const userColor = getColorFromPalette(user.themeColor)

// Apply to UI
const themeStyles = {
  backgroundColor: userColor.light.background,
  color: userColor.light.text,
  borderColor: userColor.light.border
}
```

### Form Integration  
```vue
<template>
  <VCard>
    <VCardTitle>User Settings</VCardTitle>
    <VCardText>
      <ColorPickerForm 
        v-model="userSettings.themeColor"
        :advanced="true"
        @color-selected="saveUserSettings"
      />
    </VCardText>
  </VCard>
</template>
```

## Architecture

- **Backend**: Laravel service provider pattern with proper model binding
- **Frontend**: Vue 3 Composition API with Vuetify components  
- **State**: Centralized color caching with reactive updates
- **API**: RESTful endpoints with UUID-based operations
- **Database**: PostgreSQL with UUID generation and CHAR columns for colors

## Requirements

- Laravel 11+
- Vue 3+
- Vuetify 3+
- PostgreSQL (for UUID support)
- PHP 8.2+
- Node.js 18+

---

Built with ❤️ for maximum reusability and flexibility.