# Roster Configuration Guide

The roster module supports flexible configuration to work with or without sites. This allows the same codebase to be used across different project types.

## Environment Configuration

Add these variables to your `.env` file:

```bash
# Basic site configuration
ROSTER_ENABLE_SITES=true
ROSTER_SITE_MODEL="Sites\SitesModule\Models\Site"
ROSTER_SITE_TABLE="sites"
ROSTER_SITE_FIELD="site_id"
ROSTER_SITE_RELATIONSHIP="site"
ROSTER_GROUPING_LABEL="Site"
```

## Usage Examples

### With Sites (Default)

```bash
ROSTER_ENABLE_SITES=true
ROSTER_SITE_MODEL="Sites\SitesModule\Models\Site"
ROSTER_SITE_TABLE="sites"
ROSTER_SITE_FIELD="site_id"
ROSTER_SITE_RELATIONSHIP="site"
ROSTER_GROUPING_LABEL="Site"
```

### Without Sites

```bash
ROSTER_ENABLE_SITES=false
```

### With Projects instead of Sites

```bash
ROSTER_ENABLE_SITES=true
ROSTER_SITE_MODEL="App\Models\Project"
ROSTER_SITE_TABLE="projects"
ROSTER_SITE_FIELD="project_id"
ROSTER_SITE_RELATIONSHIP="project"
ROSTER_GROUPING_LABEL="Project"
```

### With Locations instead of Sites

```bash
ROSTER_ENABLE_SITES=true
ROSTER_SITE_MODEL="App\Models\Location"
ROSTER_SITE_TABLE="locations"
ROSTER_SITE_FIELD="location_id"
ROSTER_SITE_RELATIONSHIP="location"
ROSTER_GROUPING_LABEL="Location"
```

## Frontend Usage

### With Sites

```vue
<RosterCalendar :sites="sites" grouping-label="Site" />
```

### Without Sites

```vue
<RosterCalendar
    :sites="[]"
    <!-- Site dropdown won't render -->
/>
```

### With Projects

```vue
<RosterCalendar :sites="projects" grouping-label="Project" />
```

## Database Schema

The system automatically handles:

-   Site validation in form requests
-   Site filtering in queries
-   Site relationships in models
-   Site data in transformers

When sites are disabled, all site-related functionality is automatically bypassed.

## Publishing Configuration

To publish the configuration file:

```bash
php artisan vendor:publish --tag=config
```

This will create `config/roster.php` where you can customize the settings.
