<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sites Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration determines whether the roster module should work with
    | sites. When enabled, rosters will be associated with sites. When disabled,
    | rosters will work without site associations.
    |
    */
    'enable_sites' => env('ROSTER_ENABLE_SITES', true),

    /*
    |--------------------------------------------------------------------------
    | Site Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which model should be used for sites. This allows flexibility
    | to use different models like Site, Project, Location, etc.
    |
    */
    'site_model' => env('ROSTER_SITE_MODEL', 'Sites\\SitesModule\\Models\\Site'),

    /*
    |--------------------------------------------------------------------------
    | Site Table Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the table name for sites validation and queries.
    |
    */
    'site_table' => env('ROSTER_SITE_TABLE', 'sites'),

    /*
    |--------------------------------------------------------------------------
    | Site Field Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the field name used to associate rosters with sites.
    | This could be 'site_id', 'project_id', 'location_id', etc.
    |
    */
    'site_field' => env('ROSTER_SITE_FIELD', 'site_id'),

    /*
    |--------------------------------------------------------------------------
    | Site Relationship Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the relationship name on the StaffRoster model.
    | This could be 'site', 'project', 'location', etc.
    |
    */
    'site_relationship' => env('ROSTER_SITE_RELATIONSHIP', 'site'),

    /*
    |--------------------------------------------------------------------------
    | Grouping Label Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the display label for the grouping entity.
    | This could be 'Site', 'Project', 'Location', etc.
    |
    */
    'grouping_label' => env('ROSTER_GROUPING_LABEL', 'Site'),
];
