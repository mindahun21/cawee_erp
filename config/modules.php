<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled Modules
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of module keys enabled for this deployment.
    | When set to null or omitted, all modules are active.
    |
    | Available modules:
    |   hr, recruitment, procurement, finance, donor_fundraising, brt,
    |   monitoring_evaluation, inventory, vehicle_management, file_sharing,
    |   car_rent, ai_intelligence, planning
    |
    */

    'enabled' => env('ENABLED_MODULES', null),

    /*
    |--------------------------------------------------------------------------
    | Module Definitions
    |--------------------------------------------------------------------------
    |
    | Each entry maps a module key to the Filament artefacts it owns:
    |   - resource_namespaces : Resource class namespace prefixes
    |   - navigation_groups   : Sidebar navigation group labels
    |   - pages               : Standalone Filament page classes
    |   - clusters            : Filament cluster classes
    |   - widget_namespaces   : Widget class namespace prefixes
    |
    */

    'definitions' => [

        'hr' => [
            'label' => 'Human Resources',
            'navigation_groups' => [
                'Human Resources',
            ],
            'resource_namespaces' => [
                'App\\Filament\\Resources\\HR',
            ],
            'pages' => [
                \App\Filament\Pages\HrDashboard::class,
            ],
            'widget_namespaces' => [
                'App\\Filament\\Widgets\\HR',
            ],
        ],

        'recruitment' => [
            'label' => 'Recruitment',
            'navigation_groups' => [
                'Recruitment',
            ],
            'resource_namespaces' => [
                'App\\Filament\\Resources\\Recruitment',
            ],
            'pages' => [
                \App\Filament\Pages\RecruitmentDashboard::class,
                \App\Filament\Pages\RecruitmentAiReport::class,
            ],
            'widget_namespaces' => [
                'App\\Filament\\Widgets\\Recruitment',
            ],
        ],

        'procurement' => [
            'label' => 'Procurement',
            'navigation_groups' => [
                'Procurement',
            ],
            'resource_namespaces' => [
                'App\\Filament\\Resources\\Procurement',
            ],
            'pages' => [
                \App\Filament\Pages\ProcurementDashboard::class,
                \App\Filament\Pages\ProcurementReports::class,
            ],
            'widget_namespaces' => [
                'App\\Filament\\Widgets\\Procurement',
            ],
        ],

        'finance' => [
            'label' => 'Finance',
            'navigation_groups' => [
                'Finance',
            ],
            'resource_namespaces' => [
                'App\\Filament\\Resources\\Finance',
                'App\\Filament\\Resources\\Currencies',
            ],
            'pages' => [
                \App\Filament\Pages\Finance\FinanceDashboard::class,
                \App\Filament\Pages\Finance\FinanceReports::class,
            ],
            'widget_namespaces' => [
                'App\\Filament\\Widgets\\Finance',
            ],
        ],

        'donor_fundraising' => [
            'label' => 'Donor Fundraising',
            'navigation_groups' => [
                'Donor Fundraising',
                'Donor Fundraising / Settings',
            ],
            'resource_namespaces' => [
                'App\\Filament\\Resources\\Donations',
                'App\\Filament\\Resources\\Donors',
                'App\\Filament\\Resources\\Campaigns',
                'App\\Filament\\Resources\\CampaignEvents',
            ],
            'pages' => [
                \App\Filament\Pages\DonorFundraisingDashboard::class,
                \App\Filament\Pages\DonorReports::class,
            ],
            'widget_namespaces' => [
                'App\\Filament\\Widgets\\DonorManagement',
            ],
        ],

        'brt' => [
            'label' => 'Beneficiary Registry & Project Tracking',
            'navigation_groups' => [
                'Beneficiary Registry & Project Tracking',
            ],
            'resource_namespaces' => [
                'App\\Filament\\Resources\\BRT',
            ],
        ],

        'monitoring_evaluation' => [
            'label' => 'Monitoring and Evaluation',
            'navigation_groups' => [
                'Monitoring and Evaluation',
            ],
            'resource_namespaces' => [
                'App\\Filament\\Resources\\ME',
            ],
            'pages' => [
                \App\Filament\Pages\ME\MeDashboard::class,
                \App\Filament\Pages\ME\ImportWeeklyReport::class,
            ],
            'widget_namespaces' => [
                'App\\Filament\\Widgets\\ME',
            ],
        ],

        'inventory' => [
            'label' => 'Inventory and Asset',
            'navigation_groups' => [
                'Inventory and Asset',
            ],
            'resource_namespaces' => [
                'App\\Filament\\Resources\\Inventory',
                'App\\Filament\\Resources\\InventoryMovements',
                'App\\Filament\\Resources\\Items',
                'App\\Filament\\Resources\\Warehouses',
                'App\\Filament\\Resources\\Assets',
                'App\\Filament\\Resources\\AssetAssignments',
                'App\\Filament\\Resources\\Maintenances',
            ],
            'pages' => [
                \App\Filament\Pages\InventoryDashboard::class,
            ],
            'widget_namespaces' => [
                'App\\Filament\\Widgets\\InventoryOverview',
                'App\\Filament\\Widgets\\LowStockWidget',
                'App\\Filament\\Widgets\\AssetStatusChart',
                'App\\Filament\\Widgets\\MaintenanceAlertsWidget',
                'App\\Filament\\Widgets\\MovementTrendChart',
                'App\\Filament\\Widgets\\StockByWarehouseChart',
                'App\\Filament\\Widgets\\StockValueChart',
            ],
        ],

        'vehicle_management' => [
            'label' => 'Vehicle Management',
            'navigation_groups' => [
                'Vehicle Management',
            ],
            'resource_namespaces' => [
                'App\\Filament\\Resources\\Vehicles',
                'App\\Filament\\Resources\\VehicleAssignments',
                'App\\Filament\\Resources\\VehicleManagement',
                'App\\Filament\\Resources\\VehicleSettings',
            ],
        ],

        'file_sharing' => [
            'label' => 'File Sharing',
            'navigation_groups' => [
                'File Sharing',
            ],
            'resource_namespaces' => [
                'App\\Filament\\Resources\\FileSharing',
            ],
            'pages' => [
                \App\Filament\Pages\FileSharingReports::class,
            ],
        ],

        'car_rent' => [
            'label' => 'Car Rent Management',
            'navigation_groups' => [
                'Car Rent Management',
            ],
            'clusters' => [
                \App\Filament\Clusters\CarRentManagement::class,
            ],
        ],

        'ai_intelligence' => [
            'label' => 'AI Intelligence',
            'navigation_groups' => [
                'AI Intelligence',
            ],
            'pages' => [
                \App\Filament\Pages\AiAnalyticsHub::class,
            ],
        ],

        'planning' => [
            'label' => 'Planning & Reporting',
            'clusters' => [
                \App\Filament\Clusters\Planning::class,
            ],
        ],

    ],

];
