<?php

use App\Shared\Reporting\CareInsightsProvider;
use App\Shared\Reporting\MaintenanceInsightsProvider;
use App\Shared\Reporting\SupplyInsightsProvider;
use App\Shared\Reporting\TicketingInsightsProvider;

return [
    'modules' => [
        'ticketing' => [
            'label' => 'Ticketing',
            'route' => '/tickets',
            'icon' => '✉',
            'permission' => 'use ticketing',
            'description' => 'Raise, track and resolve support requests.',
            'menus' => [
                ['label' => 'My Tickets', 'route' => '/tickets'],
                ['label' => 'New Ticket', 'route' => '/tickets/create'],
                ['label' => 'All Tickets', 'route' => '/tickets?scope=all', 'roles' => ['SuperAdmin', 'Admin', 'Support']],
                ['label' => 'Reports', 'route' => '/reports', 'roles' => ['SuperAdmin', 'Admin', 'Support']],
                ['label' => 'Officers & Faces', 'route' => '/officers', 'roles' => ['SuperAdmin', 'Admin', 'Support']],
                ['label' => 'Knowledge Base', 'route' => '/kb'],
            ],
        ],
        'care' => [
            'label' => 'Care Network',
            'route' => '/care',
            'icon' => '✚',
            'permission' => 'use care workspace',
            'room_permissions' => ['use care workspace', 'use care portal'],
            'description' => 'Patient registration, clinical workflows and the patient portal.',
            'menus' => [
                ['label' => 'Care Dashboard', 'route' => '/care', 'permission' => 'use care workspace'],
                ['label' => 'Registrations', 'route' => '/care/registrations', 'permission' => 'use care workspace'],
                ['label' => 'Appointments', 'route' => '/care/appointments', 'permission' => 'use care workspace'],
                ['label' => 'Visits', 'route' => '/care/visits', 'permission' => 'use care workspace'],
                ['label' => 'Lab', 'route' => '/care/lab', 'permission' => 'use care workspace'],
                ['label' => 'Admissions', 'route' => '/care/admissions', 'permission' => 'manage admissions'],
                ['label' => 'Referrals', 'route' => '/care/referrals', 'permission' => 'manage referrals'],
                ['label' => 'Invoices', 'route' => '/care/invoices', 'permission' => 'use care workspace'],
                ['label' => 'Duty Certificates', 'route' => '/care/duty-certificates', 'permission' => 'use care workspace'],
                ['label' => 'Care Reports', 'route' => '/care/reports', 'permission' => 'view reports'],
                ['label' => 'Patient Dashboard', 'route' => '/portal', 'permission' => 'use care portal'],
                ['label' => 'My Registrations', 'route' => '/portal/registrations', 'permission' => 'use care portal'],
                ['label' => 'My Appointments', 'route' => '/portal/appointments', 'permission' => 'use care portal'],
                ['label' => 'My Invoices', 'route' => '/portal/invoices', 'permission' => 'use care portal'],
                ['label' => 'My Results', 'route' => '/portal/results', 'permission' => 'use care portal'],
            ],
        ],
        'care-portal' => [
            'label' => 'Patient Portal',
            'route' => '/portal',
            'icon' => '✚',
            'permission' => 'use care portal',
        ],
        'maintenance' => [
            'label' => 'Maintenance',
            'route' => '/maintenance',
            'icon' => '⚙',
            'permission' => 'use maintenance',
            'description' => 'Work orders, faults and preventive maintenance.',
            'menus' => [
                ['label' => 'Work Orders', 'route' => '/maintenance', 'permission' => 'use maintenance'],
                ['label' => 'New Work Order', 'route' => '/maintenance/create', 'permission' => 'use maintenance'],
            ],
        ],
        'supply' => [
            'label' => 'Supply',
            'route' => '/supply',
            'icon' => '▤',
            'permission' => 'use supply',
            'description' => 'Stock levels, receipts, issues and approvals.',
            'menus' => [
                ['label' => 'Stock Levels', 'route' => '/supply', 'permission' => 'use supply'],
                ['label' => 'Stock Approvals', 'route' => '/supply/approvals', 'permission' => 'approve stock adjustments'],
                ['label' => 'Supply Categories', 'route' => '/admin/supply-categories', 'roles' => ['SuperAdmin', 'Admin']],
            ],
        ],
    ],

    'insights_providers' => [
        TicketingInsightsProvider::class,
        CareInsightsProvider::class,
        MaintenanceInsightsProvider::class,
        SupplyInsightsProvider::class,
    ],
];
