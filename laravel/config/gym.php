<?php

return [
    'password_reset_delivery' => env('FITFUSION_PASSWORD_RESET_DELIVERY', 'preview'),

    // The Owner grants these individually. New managers have no permissions.
    'manager_permissions' => [
        'trainers.manage' => 'Manage trainers',
        'workers.manage' => 'Manage workers',
        'staff.schedules' => 'Manage staff schedules',
        'staff.attendance' => 'Manage staff attendance',
        'clients.manage' => 'Manage clients',
        'memberships.manage' => 'Manage memberships',
        'workouts.manage' => 'Manage workout plans',
        'diets.manage' => 'Manage diet plans',
        'feedback.manage' => 'Moderate Client feedback',
        'messages.manage' => 'Manage messages and inquiries',
        'reports.view' => 'View operational reports',
        'finance.view' => 'View financial information',
        'finance.manage' => 'Record payments and expenses',
    ],
];

