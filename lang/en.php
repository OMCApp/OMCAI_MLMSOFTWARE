<?php
/**
 * English translations
 *
 * Keys must match lang/th.php exactly — verify with: php scripts/check.php lang
 * No HTML tags allowed here; placeholders are escaped by the helper.
 */

declare(strict_types=1);

return [

    'date' => [
        'year_offset'  => 0,
        'months_short' => [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
        ],
        'today'      => 'Today',
        'this_week'  => 'This week',
        'this_month' => 'This month',
        'six_months' => '6 months',
        'one_year'   => '1 year',
        'from'       => 'From',
        'to'         => 'To',
    ],

    'common' => [
        'save'        => 'Save',
        'cancel'      => 'Cancel',
        'delete'      => 'Delete',
        'edit'        => 'Edit',
        'view'        => 'View details',
        'create'      => 'Add new',
        'search'      => 'Search',
        'filter'      => 'Filters',
        'clear'       => 'Clear filters',
        'export'      => 'Export CSV',
        'back'        => 'Back',
        'confirm'     => 'Confirm',
        'close'       => 'Close',
        'loading'     => 'Loading',
        'per_page'    => 'Rows per page',
        'showing'     => 'Showing :from-:to of :total',
        'required'    => 'Required',
        'optional'    => 'Optional',
        'yes'         => 'Yes',
        'no'          => 'No',
        'actions'     => 'Actions',
        'created_at'  => 'Created',
        'updated_at'  => 'Last updated',
    ],

    'state' => [
        'empty_title'    => 'Nothing here yet',
        'empty_hint'     => 'Use the add button to get started',
        'no_result'      => 'No records match your filters',
        'no_result_hint' => 'Try clearing the filters and searching again',
        'error_title'    => 'Something went wrong',
        'error_hint'     => 'Please try again',
        'retry'          => 'Retry',
        'reference'      => 'Reference code',
    ],

    'flash' => [
        'created' => ':name has been added',
        'updated' => 'Changes to :name have been saved',
        'deleted' => ':name has been deleted',
        'failed'  => 'The action could not be completed',
    ],

    'confirm' => [
        'delete_title' => 'Delete :name ?',
        'delete_body'  => 'This record will be hidden from the system but can still be restored later',
    ],

    'auth' => [
        'login'            => 'Sign in',
        'logout'           => 'Sign out',
        'username'         => 'Username',
        'password'         => 'Password',
        'remember'         => 'Remember me',
        'failed'           => 'Incorrect username or password',
        'throttled'        => 'Too many attempts. Please wait :minutes minutes',
        'logged_out'       => 'You have been signed out',
        'session_expired'  => 'Your session has expired. Please sign in again',
    ],

    'validation' => [
        'required'  => ':field is required',
        'email'     => ':field must be a valid email address',
        'integer'   => ':field must be a whole number',
        'numeric'   => ':field must be a number',
        'min'       => ':field must be at least :value',
        'max'       => ':field must not exceed :value',
        'min_len'   => ':field must be at least :value characters',
        'max_len'   => ':field must not exceed :value characters',
        'in'        => ':field has an invalid value',
        'date'      => ':field must be a valid date',
        'confirmed' => ':field does not match its confirmation',
        'unique'    => 'This :field is already taken',
    ],

    'upload' => [
        'choose'      => 'Choose file',
        'drop_here'   => 'Drop files here',
        'accepted'    => 'Accepts :types up to :size',
        'too_large'   => ':name exceeds :size',
        'bad_type'    => ':name is not an accepted file type',
        'failed'      => 'Could not upload :name',
        'remove'      => 'Remove',
        'drop_active' => 'Drop to attach',
        'uploading'   => 'Uploading',
    ],

    'error' => [
        '403_title' => 'Access denied',
        '403_body'  => 'Your account does not have permission to view this page. Contact an administrator if you believe this is a mistake',
        '404_title' => 'Page not found',
        '404_body'  => 'This page may have been moved or removed',
        '500_title' => 'System error',
        '500_body'  => 'Something went wrong on our side. The team has been notified',
        'maintenance_title' => 'Under maintenance',
        'maintenance_body'  => 'Please check back in a few moments',
        'csrf'      => 'This form has expired. Please try again',
        'go_home'   => 'Back to home',
    ],

    'audit' => [
        'action_create'            => 'Created',
        'action_update'            => 'Updated',
        'action_delete'            => 'Deleted',
        'action_restore'           => 'Restored',
        'action_login'             => 'Signed in',
        'action_login_failed'      => 'Sign-in failed',
        'action_logout'            => 'Signed out',
        'action_permission_change' => 'Permission changed',
        'action_setting_change'    => 'Setting changed',
        'action_export'            => 'Exported data',
        'action_view_sensitive'    => 'Viewed sensitive data',
    ],

    'ui' => [
        'language'     => 'Language',
        'theme'        => 'Appearance',
        'theme_light'  => 'Light',
        'theme_dark'   => 'Dark',
        'menu'         => 'Menu',
        'collapse'     => 'Collapse menu',
        'expand'       => 'Expand menu',
        'manual'       => 'User manual',
        'support'      => 'Report an issue',
        'breadcrumb'   => 'Breadcrumb',

        // Strings the JS side needs — passed through partials/js-lang.php
        'cancel'       => 'Cancel',
        'clear'        => 'Clear',
        'search'       => 'Search',
        'no_results'   => 'Nothing matched your search',
        'close'        => 'Close',
        'loading'      => 'Loading',
        'load_more'    => 'Show more',
        'type_more'    => 'Type at least {n} characters to search',
    ],

    'support' => [
        'title'          => 'Report an issue',
        'type_bug'       => 'Report a bug',
        'type_feature'   => 'Request a feature',
        'type_how_to'    => 'How do I use this',
        'open_manual'    => 'Full user manual',
        'still_stuck'    => 'Still stuck, ask an admin',
        'message'        => 'Describe what you want to report',
        'attachment'     => 'Attach a screenshot',
        'submit'         => 'Send',
        'received'       => 'We have received your report, thank you',
        'throttled'      => 'Too many reports sent. Please wait a moment',
        'status_new'          => 'New',
        'status_acknowledged' => 'Acknowledged',
        'status_in_progress'  => 'In progress',
        'status_done'         => 'Done',
        'status_rejected'     => 'Rejected',
    ],

    'manual' => [
        'title'        => 'User manual',
        'lead'         => 'Pick a topic — every article walks through the steps with screenshots',
        'not_found'    => 'There is no manual article for this page yet',
        'no_translate' => 'This article has not been translated yet, showing the default language instead',

        // Group names must be translatable — never show raw keys from config/manuals.php
        'group' => [
            'basics' => 'Getting started',
            'admin'  => 'For administrators',
        ],
    ],
];
