<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Site Integration
    |--------------------------------------------------------------------------
    | Configure whether the shift log module should work with sites
    */
    'enable_sites' => env('SHIFT_LOG_ENABLE_SITES', true),
    'site_model' => env('SHIFT_LOG_SITE_MODEL', 'Sites\\SitesModule\\Models\\Site'),
    'site_table' => env('SHIFT_LOG_SITE_TABLE', 'sites'),
    'site_field' => env('SHIFT_LOG_SITE_FIELD', 'site_id'),
    'site_relationship' => env('SHIFT_LOG_SITE_RELATIONSHIP', 'site'),

    /*
    |--------------------------------------------------------------------------
    | Staff Integration
    |--------------------------------------------------------------------------
    | Configure staff model and table references
    */
    'staff_model' => env('SHIFT_LOG_STAFF_MODEL', 'App\\Models\\User'),
    'staff_table' => env('SHIFT_LOG_STAFF_TABLE', 'users'),
    'staff_field' => env('SHIFT_LOG_STAFF_FIELD', 'staff_id'),

    /*
    |--------------------------------------------------------------------------
    | Optional Features
    |--------------------------------------------------------------------------
    | Enable/disable optional features based on project requirements
    */
    'enable_breaks' => env('SHIFT_LOG_ENABLE_BREAKS', false),
    'enable_overtime' => env('SHIFT_LOG_ENABLE_OVERTIME', false),
    'enable_photos' => env('SHIFT_LOG_ENABLE_PHOTOS', false),
    'enable_ip_tracking' => env('SHIFT_LOG_ENABLE_IP_TRACKING', false),
    'enable_comments' => env('SHIFT_LOG_ENABLE_COMMENTS', true),

    /*
    |--------------------------------------------------------------------------
    | Break Configuration
    |--------------------------------------------------------------------------
    | Configuration for break tracking when enabled
    */
    'break_table' => env('SHIFT_LOG_BREAK_TABLE', 'staff_roster_shift_breaks'),
    'approved_breaks_table' => env('SHIFT_LOG_APPROVED_BREAKS_TABLE', 'staff_approved_breaks'),

    /*
    |--------------------------------------------------------------------------
    | Overtime Configuration
    |--------------------------------------------------------------------------
    | Configuration for overtime tracking when enabled
    */
    'overtime_approval_required' => env('SHIFT_LOG_OVERTIME_APPROVAL_REQUIRED', true),
    'overtime_threshold_minutes' => env('SHIFT_LOG_OVERTIME_THRESHOLD_MINUTES', 0),

    /*
    |--------------------------------------------------------------------------
    | Media Configuration
    |--------------------------------------------------------------------------
    | Configuration for photo uploads when enabled
    */
    'photo_collections' => [
        'check_in_photos' => 'check-in-photos',
        'check_out_photos' => 'check-out-photos',
        'shift_log_general' => 'shift-log-general',
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Labels
    |--------------------------------------------------------------------------
    | Customizable labels for different contexts
    */
    'labels' => [
        'shift' => env('SHIFT_LOG_SHIFT_LABEL', 'Shift'),
        'check_in' => env('SHIFT_LOG_CHECKIN_LABEL', 'Check In'),
        'check_out' => env('SHIFT_LOG_CHECKOUT_LABEL', 'Check Out'),
        'break' => env('SHIFT_LOG_BREAK_LABEL', 'Break'),
        'overtime' => env('SHIFT_LOG_OVERTIME_LABEL', 'Overtime'),
        'grouping_label' => env('SHIFT_LOG_GROUPING_LABEL', 'Site'),
    ],
];
