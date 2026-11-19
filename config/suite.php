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
        ],
        'care' => [
            'label' => 'Care Network',
            'route' => '/care',
            'icon' => '✚',
            'permission' => 'use care workspace',
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
        ],
        'supply' => [
            'label' => 'Supply',
            'route' => '/supply',
            'icon' => '▤',
            'permission' => 'use supply',
        ],
    ],

    'insights_providers' => [
        TicketingInsightsProvider::class,
        CareInsightsProvider::class,
        MaintenanceInsightsProvider::class,
        SupplyInsightsProvider::class,
    ],
];
