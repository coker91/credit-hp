<?php

namespace App\Providers;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Widgets\Widget;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Keep permission names compatible with the existing Shield v3 data
        // and policies while using Shield v4 on Filament v5.
        FilamentShield::buildPermissionKeyUsing(
            function (string $entity, ?string $affix, string $subject): string {
                if (is_subclass_of($entity, Resource::class)) {
                    return Str::snake("{$affix}_{$subject}");
                }

                if (is_subclass_of($entity, Page::class)) {
                    return 'page_'.class_basename($entity);
                }

                if (is_subclass_of($entity, Widget::class)) {
                    return 'widget_'.class_basename($entity);
                }

                return $subject;
            },
        );
    }
}
