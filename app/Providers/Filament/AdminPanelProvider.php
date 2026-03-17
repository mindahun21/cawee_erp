<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('ELISOFT ERP')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => Color::Amber,
            ])
            // Full width content — no more scrolling through narrow tables
            ->maxContentWidth(\Filament\Support\Enums\Width::Full)
            // Sidebar collapses to icons on desktop for more workspace
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->databaseNotifications()
            ->userMenuItems([
                \Filament\Navigation\MenuItem::make()
                    ->label('My Profile')
                    ->icon('heroicon-o-user-circle')
                    ->url(fn (): string => \App\Filament\Pages\MyProfile::getUrl()),
            ])
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make('Human Resources')
                    ->collapsible(),
                \Filament\Navigation\NavigationGroup::make('Procurement')
                    ->collapsible(),
                \Filament\Navigation\NavigationGroup::make('Donor Fundraising')
                    ->collapsible(),
                \Filament\Navigation\NavigationGroup::make('Donor Fundraising / Reports')
                    ->collapsible(),
                \Filament\Navigation\NavigationGroup::make('Donor Fundraising / Settings')
                    ->collapsible(),
                \Filament\Navigation\NavigationGroup::make('Inventory and Asset')
                    ->collapsible(),
                \Filament\Navigation\NavigationGroup::make('System Administration')
                    ->collapsible(),
            ])
            ->navigationItems([
                \Filament\Navigation\NavigationItem::make('Leave Requests')
                    ->group('Human Resources')
                    ->icon('heroicon-o-calendar-days')
                    ->sort(90)
                    ->url(fn (): string => \App\Filament\Resources\HR\LeaveRequests\LeaveRequestResource::getUrl())
                    ->visible(fn () => auth()->user()->hasRole('super_admin') || auth()->user()->can('ViewAny:LeaveRequest')),
                \Filament\Navigation\NavigationItem::make('Timesheet Management')
                    ->group('Human Resources')
                    ->icon('heroicon-o-clock')
                    ->sort(91)
                    ->url(fn (): string => \App\Filament\Resources\HR\Timesheets\TimesheetResource::getUrl())
                    ->visible(fn () => auth()->user()->hasRole('super_admin') || auth()->user()->can('ViewAny:HrTimesheet')),
                \Filament\Navigation\NavigationItem::make('HR Settings')
                    ->group('Human Resources')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->sort(93)
                    ->url(fn (): string => \App\Filament\Resources\HR\Settings\DepartmentResource::getUrl())
                    ->visible(fn () => auth()->user()->hasRole('super_admin') || auth()->user()->can('ViewAny:Department')),

                \Filament\Navigation\NavigationItem::make('Settings')
                    ->group('Procurement')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->sort(99)
                    ->url(fn (): string => \App\Filament\Resources\Procurement\Settings\ProcurementCurrencyResource::getUrl())
                    ->isActiveWhen(fn () => request()->routeIs([
                        \App\Filament\Resources\Procurement\Settings\ProcurementCurrencyResource::getRouteBaseName() . '.*',
                        \App\Filament\Resources\Procurement\Settings\ProcurementCategoryResource::getRouteBaseName() . '.*',
                        \App\Filament\Resources\Procurement\Settings\ProcurementMethodResource::getRouteBaseName() . '.*',
                        \App\Filament\Resources\Procurement\Settings\ProcurementUnitResource::getRouteBaseName() . '.*',
                        \App\Filament\Resources\Procurement\Settings\BidSecurityResource::getRouteBaseName() . '.*',
                        \App\Filament\Resources\Procurement\Settings\ContractTypeResource::getRouteBaseName() . '.*',
                        \App\Filament\Resources\Procurement\Settings\ApprovalWorkflowResource::getRouteBaseName() . '.*',
                        \App\Filament\Resources\Procurement\Budgets\ProcurementBudgetResource::getRouteBaseName() . '.*',
                    ])),
            ])
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
