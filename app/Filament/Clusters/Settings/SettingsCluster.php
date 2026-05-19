<?php

namespace App\Filament\Clusters\Settings;

use Filament\Clusters\Cluster;
use Filament\Pages\SubNavigationPosition;
use Illuminate\Contracts\Support\Htmlable;

class SettingsCluster extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static string $view = 'filament.clusters.settings';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return __('Settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('Settings');
    }

    public function mount(): void
    {
        // Show the index page instead of redirecting to the first child
    }

    public static function getClusterBreadcrumb(): string
    {
        return __('Settings');
    }
}
